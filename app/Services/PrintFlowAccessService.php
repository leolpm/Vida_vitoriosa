<?php

namespace App\Services;

use App\Exceptions\PrintFlowAccessException;
use App\Models\PrintFlow;
use App\Models\PrintFlowToken;
use App\Support\CurrentEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintFlowAccessService
{
    public function __construct(
        private readonly CurrentEvent $currentEvent,
        private readonly PrintFlowManager $manager,
    ) {}

    public function resolve(string $plainToken, Request $request): PrintFlow
    {
        $token = PrintFlowToken::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $token) {
            throw new PrintFlowAccessException('Este link não existe ou já foi substituído.', 404);
        }

        $flow = PrintFlow::withoutGlobalScopes()
            ->with(['event', 'participant', 'teamMember'])
            ->find($token->print_flow_id);

        if (! $flow) {
            throw new PrintFlowAccessException('Este fluxo não está mais disponível.', 404);
        }

        if ((int) $flow->event_id !== $this->currentEvent->id()) {
            throw new PrintFlowAccessException('Este link pertence a outro evento. Solicite um novo link à equipe responsável.');
        }

        if ($flow->status === 'cancelled') {
            throw new PrintFlowAccessException('Este fluxo foi cancelado e não pode mais ser acessado.', 410);
        }

        if ($token->invalidated_at) {
            throw new PrintFlowAccessException('Este link foi invalidado. Solicite um novo link à equipe responsável.', 410);
        }

        if ($token->isExpired()) {
            throw new PrintFlowAccessException('Este link expirou. Solicite um novo link à equipe responsável.', 410);
        }

        $sessionKey = $this->sessionKey($token);

        if (! (bool) $request->session()->get($sessionKey, false)) {
            if (! $token->hasCapacity()) {
                throw new PrintFlowAccessException('O limite de acessos deste link foi atingido. Solicite um novo link.', 410);
            }

            DB::transaction(function () use ($token, $request, $sessionKey): void {
                $locked = PrintFlowToken::query()->lockForUpdate()->findOrFail($token->id);

                if (! $locked->isUsable()) {
                    throw new PrintFlowAccessException('Este link não está mais disponível. Solicite um novo link.', 410);
                }

                $now = now();
                $locked->update([
                    'accesses_used' => $locked->accesses_used + 1,
                    'first_accessed_at' => $locked->first_accessed_at ?: $now,
                    'last_accessed_at' => $now,
                ]);
                $request->session()->put($sessionKey, true);
            });

            if ($flow->status === 'distributed') {
                $before = ['status' => $flow->status, 'current_step' => $flow->current_step];
                $flow->update(['status' => 'in_review']);
                $this->manager->audit($flow, 'team_member', $flow->team_member_id, 'token_accessed', $before, [
                    'status' => $flow->status,
                    'token_id' => $token->id,
                ], $request);
            }
        }

        return $flow->fresh(['event', 'participant', 'teamMember']);
    }

    private function sessionKey(PrintFlowToken $token): string
    {
        return 'print_flow_access.'.$token->print_flow_id.'.'.$token->id;
    }
}
