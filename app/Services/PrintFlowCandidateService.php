<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\PrintFlow;
use App\Models\PrintFlowReview;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Support\CurrentEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PrintFlowCandidateService
{
    public function __construct(private readonly CurrentEvent $currentEvent) {}

    public function options(string $type, bool $includeReevaluated = false): array
    {
        return [
            'type' => $type,
            'participants' => $this->candidates($type, $includeReevaluated)
                ->map(fn (Participant $participant): array => $this->participantPayload($participant, $type))
                ->values(),
            'members' => $this->availableMembers()
                ->map(fn (TeamMember $member): array => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'open_tasks_count' => $member->open_tasks_count,
                    'effective_limit' => $member->effective_limit,
                    'available_slots' => $member->available_slots,
                    'label' => "{$member->name} - {$member->open_tasks_count}/{$member->effective_limit} tarefas - {$member->available_slots} vaga(s)",
                ])->values(),
        ];
    }

    public function dashboardData(): array
    {
        $criticalParticipants = $this->criticalParticipants();
        $mainCandidates = $this->candidates('main_print');
        $reviewCandidates = $this->candidates('reevaluation');

        return [
            'critical_participants' => $criticalParticipants,
            'critical_count' => $criticalParticipants->count(),
            'critical_with_open_task_count' => $criticalParticipants->where('has_open_search_task', true)->count(),
            'main_candidates_count' => $mainCandidates->count(),
            'main_letters_count' => $mainCandidates->sum('eligible_testimonials_count'),
            'review_candidates_count' => $reviewCandidates->count(),
            'review_letters_count' => $reviewCandidates->sum('eligible_testimonials_count'),
            'minimum_testimonials' => $this->minimumTestimonials(),
        ];
    }

    public function candidates(string $type, bool $includeReevaluated = false): Collection
    {
        if ($type === 'testimonial_search') {
            return $this->criticalParticipants()
                ->reject(fn (Participant $participant): bool => (bool) $participant->has_open_search_task)
                ->values();
        }

        $participants = Participant::active()->orderBy('name')->get();
        if ($participants->isEmpty()) {
            return collect();
        }

        $testimonials = $this->testimonialQuery()
            ->whereIn('participant_id', $participants->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('participant_id');

        return $participants->map(function (Participant $participant) use ($testimonials, $type, $includeReevaluated): Participant {
            $eligible = $testimonials->get($participant->id, collect())
                ->filter(fn (Testimonial $testimonial): bool => $this->testimonialIsEligible($testimonial, $type, $includeReevaluated))
                ->values();

            $participant->setRelation('eligibleTestimonials', $eligible);
            $participant->setAttribute('eligible_testimonials_count', $eligible->count());

            return $participant;
        })->filter(fn (Participant $participant): bool => $participant->eligible_testimonials_count > 0)->values();
    }

    public function eligibleTestimonialsForParticipant(
        int $participantId,
        string $type,
        bool $includeReevaluated = false,
    ): Collection {
        if ($type === 'testimonial_search') {
            return collect();
        }

        return $this->testimonialQuery()
            ->where('participant_id', $participantId)
            ->orderBy('created_at')
            ->get()
            ->filter(fn (Testimonial $testimonial): bool => $this->testimonialIsEligible($testimonial, $type, $includeReevaluated))
            ->values();
    }

    public function testimonialIsEligible(
        Testimonial $testimonial,
        string $type,
        bool $includeReevaluated = false,
    ): bool {
        if ((int) $testimonial->event_id !== $this->currentEvent->id() || $testimonial->status === 'archived') {
            return false;
        }

        $testimonial->loadMissing([
            'printFlows:id,status,type',
            'printFlowReviews' => fn ($query) => $query->with(['teamMember:id,name', 'printFlow:id,type,status']),
        ]);

        if ($testimonial->printFlows->contains(fn (PrintFlow $flow): bool => $flow->isOpen())) {
            return false;
        }

        $reviews = $this->orderedReviews($testimonial);

        if ($type === 'main_print') {
            return $testimonial->status === 'approved' && $reviews->isEmpty();
        }

        if ($type !== 'reevaluation') {
            return false;
        }

        $latestReview = $reviews->first();
        if (! $latestReview || $latestReview->decision !== 'rejected') {
            return false;
        }

        return $includeReevaluated || $this->reevaluationCount($reviews) === 0;
    }

    public function searchParticipantIsEligible(Participant $participant): bool
    {
        if ((int) $participant->event_id !== $this->currentEvent->id() || $participant->status !== 'active') {
            return false;
        }

        $testimonialCount = Testimonial::query()
            ->where('participant_id', $participant->id)
            ->where('status', '!=', 'archived')
            ->count();

        $hasOpenTask = PrintFlow::query()
            ->where('participant_id', $participant->id)
            ->where('type', 'testimonial_search')
            ->whereIn('status', PrintFlow::OPEN_STATUSES)
            ->exists();

        return $testimonialCount < $this->minimumTestimonials() && ! $hasOpenTask;
    }

    public function availableMembers(): Collection
    {
        $globalLimit = max(1, (int) Setting::valueFor('print_flow_global_task_limit', 3));

        return TeamMember::active()
            ->whereHas('events', fn ($query) => $query
                ->whereKey($this->currentEvent->id())
                ->where('event_team_member.is_active', true))
            ->withCount(['printFlows as open_tasks_count' => fn ($query) => $query
                ->withoutGlobalScopes()
                ->whereIn('status', PrintFlow::OPEN_STATUSES)])
            ->orderBy('name')
            ->get()
            ->map(function (TeamMember $member) use ($globalLimit): TeamMember {
                $limit = $member->task_limit ?: $globalLimit;
                $member->setAttribute('effective_limit', $limit);
                $member->setAttribute('available_slots', max(0, $limit - $member->open_tasks_count));

                return $member;
            })
            ->filter(fn (TeamMember $member): bool => $member->available_slots > 0)
            ->values();
    }

    public function minimumTestimonials(): int
    {
        $default = $this->currentEvent->get()->slug === 'edd' ? 2 : 3;

        return max(1, (int) Setting::valueFor('print_flow_min_testimonials', $default));
    }

    private function criticalParticipants(): Collection
    {
        $minimum = $this->minimumTestimonials();

        return Participant::active()
            ->withCount(['testimonials as current_testimonials_count' => fn ($query) => $query->where('status', '!=', 'archived')])
            ->with(['printFlows' => fn ($query) => $query
                ->where('type', 'testimonial_search')
                ->whereIn('status', PrintFlow::OPEN_STATUSES)])
            ->orderBy('name')
            ->get()
            ->filter(fn (Participant $participant): bool => $participant->current_testimonials_count < $minimum)
            ->map(function (Participant $participant) use ($minimum): Participant {
                $participant->setAttribute('testimonial_target', $minimum);
                $participant->setAttribute('has_open_search_task', $participant->printFlows->isNotEmpty());

                return $participant;
            })
            ->values();
    }

    private function testimonialQuery(): Builder
    {
        return Testimonial::query()->with([
            'printFlows:id,status,type',
            'printFlowReviews' => fn ($query) => $query
                ->with(['teamMember:id,name', 'printFlow:id,type,status'])
                ->orderByDesc('decided_at')
                ->orderByDesc('id'),
        ]);
    }

    private function participantPayload(Participant $participant, string $type): array
    {
        $payload = [
            'id' => $participant->id,
            'name' => $participant->label,
        ];

        if ($type === 'testimonial_search') {
            return $payload + [
                'current_count' => $participant->current_testimonials_count,
                'target' => $participant->testimonial_target,
                'eligible_count' => 0,
                'testimonials' => [],
            ];
        }

        return $payload + [
            'eligible_count' => $participant->eligible_testimonials_count,
            'testimonials' => $participant->eligibleTestimonials
                ->map(fn (Testimonial $testimonial): array => $this->testimonialPayload($testimonial, $type))
                ->values(),
        ];
    }

    private function testimonialPayload(Testimonial $testimonial, string $type): array
    {
        $reviews = $this->orderedReviews($testimonial);
        $latestReview = $reviews->first();
        $reevaluationCount = $this->reevaluationCount($reviews);

        return [
            'id' => $testimonial->id,
            'sender_name' => $testimonial->sender_name,
            'relationship' => $testimonial->relationship === 'Outro'
                ? ($testimonial->relationship_other ?: 'Outro')
                : $testimonial->relationship,
            'created_at' => $testimonial->created_at?->format('d/m/Y H:i'),
            'review_count' => $reviews->count(),
            'reevaluation_count' => $reevaluationCount,
            'last_decision' => $latestReview?->decision,
            'last_rejection_reason' => $latestReview?->rejection_reason,
            'last_reviewer' => $latestReview?->teamMember?->name,
            'last_reviewed_at' => $latestReview?->decided_at?->format('d/m/Y H:i'),
            'review_state' => $this->reviewState($type, $latestReview, $reevaluationCount),
            'history' => $reviews->map(fn (PrintFlowReview $review): array => [
                'reviewer' => $review->teamMember?->name ?: 'Membro não identificado',
                'flow_type' => $review->printFlow?->type_label ?: 'Fluxo não identificado',
                'decision' => $review->decision === 'approved' ? 'Aprovada' : 'Reprovada',
                'reason' => $review->rejection_reason,
                'decided_at' => $review->decided_at?->format('d/m/Y H:i'),
            ])->values(),
        ];
    }

    private function orderedReviews(Testimonial $testimonial): Collection
    {
        return $testimonial->printFlowReviews
            ->sortByDesc(fn (PrintFlowReview $review): string => sprintf(
                '%s-%020d',
                $review->decided_at?->format('YmdHis.u') ?? '',
                $review->id,
            ))
            ->values();
    }

    private function reevaluationCount(Collection $reviews): int
    {
        return $reviews->filter(
            fn (PrintFlowReview $review): bool => $review->printFlow?->type === 'reevaluation'
        )->count();
    }

    private function reviewState(string $type, ?PrintFlowReview $latestReview, int $reevaluationCount): string
    {
        if ($type === 'main_print' || ! $latestReview) {
            return 'Não revisada';
        }

        if ($reevaluationCount === 0) {
            return 'Reprovada - aguardando reavaliação';
        }

        return 'Já reavaliada '.$reevaluationCount.' vez(es)';
    }
}
