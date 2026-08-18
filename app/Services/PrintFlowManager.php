<?php

namespace App\Services;

use App\Models\PrintFlow;
use App\Models\PrintFlowAudit;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\CurrentEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrintFlowManager
{
    public function __construct(
        private readonly CurrentEvent $currentEvent,
        private readonly EventUrlGenerator $urlGenerator,
    ) {}

    public function distribute(array $data, int $userId, Request $request): array
    {
        $member = TeamMember::query()->findOrFail($data['team_member_id']);

        if (! $member->isAuthorizedFor($this->currentEvent->get())) {
            throw ValidationException::withMessages([
                'team_member_id' => 'Este membro não está autorizado a atuar neste evento.',
            ]);
        }

        $limit = $member->task_limit ?: (int) Setting::valueFor('print_flow_global_task_limit', 3);

        if ($member->openTasksCount() >= $limit) {
            throw ValidationException::withMessages([
                'team_member_id' => "Este membro já atingiu o limite global de {$limit} tarefa(s) aberta(s).",
            ]);
        }

        $testimonials = $this->testimonialsFor($data['participant_id'], $data['type']);

        if ($data['type'] !== 'testimonial_search' && $testimonials->isEmpty()) {
            throw ValidationException::withMessages([
                'participant_id' => $data['type'] === 'reevaluation'
                    ? 'Este participante não possui cartas reprovadas disponíveis para reavaliação.'
                    : 'Este participante não possui depoimentos disponíveis para revisão.',
            ]);
        }

        return DB::transaction(function () use ($data, $userId, $request, $testimonials, $member): array {
            $flow = PrintFlow::create([
                'participant_id' => $data['participant_id'],
                'team_member_id' => $member->id,
                'type' => $data['type'],
                'status' => 'distributed',
                'current_step' => $data['type'] === 'testimonial_search' ? 'search' : 'review',
                'distributed_by' => $userId,
                'distributed_at' => now(),
            ]);

            if ($testimonials->isNotEmpty()) {
                $flow->testimonials()->attach($testimonials->pluck('id')->mapWithKeys(
                    fn (int $id): array => [$id => ['event_id' => $this->currentEvent->id()]]
                ));
            }

            [$token, $plainToken] = $this->issueToken($flow);
            $this->audit($flow, 'admin', $userId, 'flow_distributed', null, [
                'type' => $flow->type,
                'team_member_id' => $member->id,
                'token_id' => $token->id,
            ], $request);

            $url = $this->urlGenerator->forEvent($this->currentEvent->get(), '/fluxos/'.$plainToken);

            return [
                'flow' => $flow,
                'access_url' => $url,
                'whatsapp_url' => $this->whatsappUrl($member, $flow, $url),
            ];
        });
    }

    public function renewToken(PrintFlow $flow, int $userId, Request $request): array
    {
        return DB::transaction(function () use ($flow, $userId, $request): array {
            $flow->tokens()->whereNull('invalidated_at')->update([
                'invalidated_at' => now(),
                'invalidation_reason' => 'renewed',
            ]);

            [$token, $plainToken] = $this->issueToken($flow);
            $this->audit($flow, 'admin', $userId, 'token_renewed', null, ['token_id' => $token->id], $request);
            $url = $this->urlGenerator->forEvent($flow->event, '/fluxos/'.$plainToken);

            return [
                'access_url' => $url,
                'whatsapp_url' => $this->whatsappUrl($flow->teamMember, $flow, $url),
            ];
        });
    }

    public function audit(
        PrintFlow $flow,
        string $actorType,
        ?int $actorId,
        string $action,
        ?array $before,
        ?array $after,
        ?Request $request = null,
    ): void {
        PrintFlowAudit::create([
            'event_id' => $flow->event_id,
            'print_flow_id' => $flow->id,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'before_data' => $before,
            'after_data' => $after,
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 1000, ''),
            'created_at' => now(),
        ]);
    }

    private function issueToken(PrintFlow $flow): array
    {
        $plainToken = Str::random(64);
        $minutes = (int) Setting::valueFor('print_flow_link_minutes', 30);
        $accessLimit = (int) Setting::valueFor('print_flow_access_limit', 1);

        $token = $flow->tokens()->create([
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes($minutes),
            'max_accesses' => max(1, $accessLimit),
        ]);

        return [$token, $plainToken];
    }

    private function testimonialsFor(int $participantId, string $type): Collection
    {
        if ($type === 'testimonial_search') {
            return collect();
        }

        $query = Testimonial::query()
            ->where('participant_id', $participantId)
            ->where('status', '!=', 'archived')
            ->orderBy('created_at');

        if ($type === 'reevaluation') {
            $query->whereIn('id', DB::table('print_flow_reviews as reviews')
                ->select('reviews.testimonial_id')
                ->where('reviews.event_id', $this->currentEvent->id())
                ->where('reviews.decision', 'rejected')
                ->whereRaw('reviews.id = (select max(latest.id) from print_flow_reviews latest where latest.testimonial_id = reviews.testimonial_id)'));
        }

        return $query->get();
    }

    private function whatsappUrl(TeamMember $member, PrintFlow $flow, ?string $url): string
    {
        $phone = preg_replace('/\D+/', '', $member->phone) ?? '';
        $message = "Olá, {$member->name}! Você recebeu uma tarefa do Fluxo de Impressão para {$flow->participant->label}. Acesse: {$url}";

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
