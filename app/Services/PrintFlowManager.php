<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\PrintFlow;
use App\Models\PrintFlowAudit;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\CurrentEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PrintFlowManager
{
    public function __construct(
        private readonly CurrentEvent $currentEvent,
        private readonly EventUrlGenerator $urlGenerator,
        private readonly PrintFlowCandidateService $candidates,
    ) {}

    public function distribute(array $data, int $userId, Request $request): array
    {
        return DB::transaction(function () use ($data, $userId, $request): array {
            $participant = Participant::active()
                ->whereKey($data['participant_id'])
                ->lockForUpdate()
                ->first();

            if (! $participant) {
                throw ValidationException::withMessages([
                    'participant_id' => 'O participante selecionado não está disponível neste evento.',
                ]);
            }

            $member = TeamMember::query()->whereKey($data['team_member_id'])->lockForUpdate()->firstOrFail();
            $this->validateMember($member);

            $testimonials = collect();
            $includeReevaluated = (bool) ($data['include_reevaluated'] ?? false);

            if ($data['type'] === 'testimonial_search') {
                if (! empty($data['testimonial_ids'])) {
                    throw ValidationException::withMessages([
                        'testimonial_ids' => 'A busca de depoimentos não permite selecionar cartas.',
                    ]);
                }

                if (! $this->candidates->searchParticipantIsEligible($participant)) {
                    throw ValidationException::withMessages([
                        'participant_id' => 'Este participante não está disponível para uma nova tarefa de busca.',
                    ]);
                }
            } else {
                $selectedIds = collect($data['testimonial_ids'] ?? [])
                    ->map(fn ($id): int => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();

                if ($selectedIds->isEmpty()) {
                    throw ValidationException::withMessages([
                        'testimonial_ids' => 'Selecione pelo menos uma carta para distribuir a tarefa.',
                    ]);
                }

                $testimonials = Testimonial::query()
                    ->where('participant_id', $participant->id)
                    ->whereIn('id', $selectedIds)
                    ->lockForUpdate()
                    ->get();

                $allEligible = $testimonials->count() === $selectedIds->count()
                    && $testimonials->every(fn (Testimonial $testimonial): bool => $this->candidates
                        ->testimonialIsEligible($testimonial, $data['type'], $includeReevaluated));

                if (! $allEligible) {
                    throw ValidationException::withMessages([
                        'testimonial_ids' => 'Uma ou mais cartas selecionadas não estão mais disponíveis para esta tarefa.',
                    ]);
                }
            }

            $flow = PrintFlow::create([
                'participant_id' => $participant->id,
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
                'testimonial_ids' => $testimonials->pluck('id')->values()->all(),
                'token_id' => $token->id,
            ], $request);

            $url = $this->urlGenerator->forEvent($this->currentEvent->get(), '/fluxos/'.$plainToken);

            $flow->load(['participant', 'teamMember', 'event']);

            return [
                'flow' => $flow,
                'access_url' => $url,
                'whatsapp_url' => $this->whatsappUrl($member, $flow, $url),
                'expires_at' => $token->expires_at,
                'max_accesses' => $token->max_accesses,
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
                'expires_at' => $token->expires_at,
                'max_accesses' => $token->max_accesses,
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

    private function validateMember(TeamMember $member): void
    {
        if (! $member->isAuthorizedFor($this->currentEvent->get())) {
            throw ValidationException::withMessages([
                'team_member_id' => 'Este membro não está autorizado a atuar neste evento.',
            ]);
        }

        $limit = $member->task_limit ?: max(1, (int) Setting::valueFor('print_flow_global_task_limit', 3));

        if ($member->openTasksCount() >= $limit) {
            throw ValidationException::withMessages([
                'team_member_id' => "Este membro já atingiu o limite global de {$limit} tarefa(s) aberta(s).",
            ]);
        }
    }

    private function whatsappUrl(TeamMember $member, PrintFlow $flow, ?string $url): string
    {
        $phone = preg_replace('/\D+/', '', $member->phone) ?? '';
        $message = "Olá, {$member->name}! Você recebeu uma tarefa de {$flow->type_label} para {$flow->participant->label} no evento {$flow->event->name}. Acesse o link temporário: {$url}";

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }
}
