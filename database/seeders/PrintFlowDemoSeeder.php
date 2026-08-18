<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Participant;
use App\Models\PrintFlow;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\CurrentEvent;
use Illuminate\Database\Seeder;

class PrintFlowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $events = Event::active()->whereIn('slug', ['vida-vitoriosa', 'edd'])->get()->keyBy('slug');

        if ($events->count() !== 2) {
            $this->command?->warn('Cadastre os eventos Vida Vitoriosa e EDD antes de executar este seeder.');

            return;
        }

        $members = $this->seedMembers($events);

        foreach ($events as $event) {
            app(CurrentEvent::class)->set($event);
            $this->seedEventFlow($event, $members);
        }
    }

    private function seedMembers($events)
    {
        return collect([
            ['name' => 'Mariana Operações', 'phone' => '+5521999993101', 'events' => ['vida-vitoriosa', 'edd']],
            ['name' => 'Rafael Impressão', 'phone' => '+5521999993102', 'events' => ['vida-vitoriosa']],
            ['name' => 'Beatriz EDD', 'phone' => '+5521999993103', 'events' => ['edd']],
        ])->mapWithKeys(function (array $data) use ($events): array {
            $member = TeamMember::query()->updateOrCreate(
                ['phone' => $data['phone']],
                ['name' => $data['name'], 'status' => 'active', 'task_limit' => null]
            );
            $member->events()->sync(collect($data['events'])->mapWithKeys(
                fn (string $slug): array => [$events[$slug]->id => ['is_active' => true]]
            ));

            return [$data['name'] => $member];
        });
    }

    private function seedEventFlow(Event $event, $members): void
    {
        $participant = Participant::active()->with('testimonials')->orderBy('name')->first();

        if (! $participant) {
            $this->command?->warn("O evento {$event->name} não possui participantes ativos.");

            return;
        }

        $member = $event->slug === 'edd' ? $members['Beatriz EDD'] : $members['Rafael Impressão'];
        $type = $participant->testimonials->isEmpty() ? 'testimonial_search' : 'main_print';
        $flow = PrintFlow::query()->updateOrCreate(
            ['participant_id' => $participant->id, 'team_member_id' => $member->id, 'type' => $type],
            [
                'status' => 'distributed',
                'current_step' => $type === 'main_print' ? 'review' : 'search',
                'distributed_by' => User::query()->where('role', 'admin')->value('id'),
                'distributed_at' => now(),
                'completed_at' => null,
                'cancelled_at' => null,
            ]
        );

        $flow->testimonials()->sync($participant->testimonials->mapWithKeys(
            fn (Testimonial $testimonial): array => [$testimonial->id => ['event_id' => $event->id]]
        ));

        $plainToken = 'demo-'.$event->slug.'-fluxo-impressao';
        $flow->tokens()->updateOrCreate(
            ['token_hash' => hash('sha256', $plainToken)],
            [
                'expires_at' => now()->addDay(),
                'max_accesses' => 5,
                'accesses_used' => 0,
                'first_accessed_at' => null,
                'last_accessed_at' => null,
                'invalidated_at' => null,
                'invalidation_reason' => null,
            ]
        );

        $this->command?->line('Fluxo local '.$event->name.': '.$this->eventDemoUrl($event->slug, $plainToken));
    }

    private function eventDemoUrl(string $slug, string $token): string
    {
        $host = $slug === 'edd' ? 'edd.atitudelaranja.test' : 'vidavitoriosa.atitudelaranja.test';

        return 'http://'.$host.':8888/fluxos/'.$token;
    }
}
