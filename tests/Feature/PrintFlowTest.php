<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Participant;
use App\Models\PrintFlow;
use App\Models\PrintFlowReview;
use App\Models\Setting;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\PrintFlowCandidateService;
use App\Services\PrintFlowManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PrintFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_distributes_selected_letter_only_to_authorized_member_and_correct_event(): void
    {
        $vida = $this->useEvent('vida-vitoriosa');
        $edd = Event::where('slug', 'edd')->firstOrFail();
        $admin = $this->admin();
        $participant = Participant::create(['name' => 'Participante Vida', 'status' => 'active']);
        $testimonial = $this->testimonial($participant, 'Carta autorizada');
        $member = TeamMember::create(['name' => 'Somente EDD', 'phone' => '+5521988888888', 'status' => 'active']);
        $member->events()->attach($edd->id, ['is_active' => true]);

        $payload = [
            'participant_id' => $participant->id,
            'team_member_id' => $member->id,
            'type' => 'main_print',
            'testimonial_ids' => [$testimonial->id],
        ];

        $this->actingAs($admin)
            ->post($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), $payload)
            ->assertSessionHasErrors('team_member_id');

        $this->assertDatabaseCount('print_flows', 0);

        $member->events()->attach($vida->id, ['is_active' => true]);
        $response = $this->actingAs($admin)
            ->post($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), $payload);

        $flow = PrintFlow::firstOrFail();
        $response->assertRedirect($this->eventUrl('vida-vitoriosa', "/admin/print-flows/{$flow->id}/share"));
        $response->assertSessionHas('flow_share');
        $this->assertDatabaseHas('print_flow_testimonial', ['print_flow_id' => $flow->id, 'testimonial_id' => $testimonial->id]);
        $this->assertDatabaseCount('print_flow_tokens', 1);
        $this->assertDatabaseCount('print_flow_audits', 1);
    }

    public function test_global_task_limit_counts_flows_from_other_events(): void
    {
        Setting::put('print_flow_global_task_limit', 1);
        $vida = $this->useEvent('vida-vitoriosa');
        $edd = Event::where('slug', 'edd')->firstOrFail();
        $member = TeamMember::create(['name' => 'Compartilhado', 'phone' => '+5521977777777', 'status' => 'active']);
        $member->events()->attach([$vida->id => ['is_active' => true], $edd->id => ['is_active' => true]]);
        $vidaParticipant = Participant::create(['name' => 'Vida', 'status' => 'active']);

        $this->useEvent('edd');
        $eddParticipant = Participant::create(['name' => 'EDD', 'status' => 'active']);
        PrintFlow::create([
            'participant_id' => $eddParticipant->id, 'team_member_id' => $member->id,
            'type' => 'testimonial_search', 'status' => 'distributed', 'current_step' => 'search', 'distributed_at' => now(),
        ]);

        $this->useEvent('vida-vitoriosa');
        $request = Request::create($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), 'POST');

        $this->expectException(ValidationException::class);
        app(PrintFlowManager::class)->distribute([
            'participant_id' => $vidaParticipant->id,
            'team_member_id' => $member->id,
            'type' => 'testimonial_search',
        ], User::factory()->create()->id, $request);
    }

    public function test_member_at_limit_is_hidden_from_distribution_options(): void
    {
        Setting::put('print_flow_global_task_limit', 1);
        $event = $this->useEvent('vida-vitoriosa');
        $admin = $this->admin();
        $participant = Participant::create(['name' => 'Participante com carta', 'status' => 'active']);
        $this->testimonial($participant, 'Carta disponível');
        $member = $this->member($event, 'Membro sem vagas', 1);
        PrintFlow::create([
            'participant_id' => $participant->id,
            'team_member_id' => $member->id,
            'type' => 'testimonial_search',
            'status' => 'distributed',
            'current_step' => 'search',
            'distributed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson($this->eventUrl('vida-vitoriosa', '/admin/print-flows/distribution-options?type=main_print'))
            ->assertOk()
            ->assertJsonPath('members', []);
    }

    public function test_member_with_history_in_another_event_cannot_be_deleted(): void
    {
        $vida = $this->useEvent('vida-vitoriosa');
        $edd = Event::where('slug', 'edd')->firstOrFail();
        $admin = $this->admin();
        $member = TeamMember::create(['name' => 'Histórico Global', 'phone' => '+5521966666666', 'status' => 'active']);
        $member->events()->attach([$vida->id => ['is_active' => true], $edd->id => ['is_active' => true]]);

        $this->useEvent('edd');
        $participant = Participant::create(['name' => 'Participante EDD', 'status' => 'active']);
        PrintFlow::create([
            'participant_id' => $participant->id,
            'team_member_id' => $member->id,
            'type' => 'testimonial_search',
            'status' => 'completed',
            'current_step' => 'complete',
            'distributed_at' => now(),
            'completed_at' => now(),
        ]);

        $this->useEvent('vida-vitoriosa');
        $this->actingAs($admin)
            ->delete($this->eventUrl('vida-vitoriosa', '/admin/team/'.$member->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('team_members', ['id' => $member->id]);
    }

    public function test_token_is_event_bound_consumed_once_per_session_and_expires(): void
    {
        [$flow, $plainToken] = $this->createDistributedFlow();

        $this->get($this->eventUrl('edd', '/fluxos/'.$plainToken))
            ->assertForbidden()
            ->assertSeeText('outro evento');

        $this->get($this->eventUrl('vida-vitoriosa', '/fluxos/'.$plainToken))->assertOk();
        $this->get($this->eventUrl('vida-vitoriosa', '/fluxos/'.$plainToken))->assertOk();
        $this->assertDatabaseHas('print_flow_tokens', ['print_flow_id' => $flow->id, 'accesses_used' => 1]);

        $flow->tokens()->update(['expires_at' => now()->subMinute()]);
        $this->get($this->eventUrl('vida-vitoriosa', '/fluxos/'.$plainToken))
            ->assertStatus(410)
            ->assertSeeText('expirou');

        $this->post($this->eventUrl('vida-vitoriosa', '/fluxos/'.$plainToken.'/concluir-revisao'))
            ->assertStatus(410)
            ->assertSeeText('expirou');
    }

    public function test_participant_without_testimonials_is_listed_as_critical(): void
    {
        $this->useEvent('vida-vitoriosa');
        $admin = $this->admin();
        Participant::create(['name' => 'Participante Sem Cartas', 'status' => 'active']);

        $this->actingAs($admin)
            ->get($this->eventUrl('vida-vitoriosa', '/admin/print-flows'))
            ->assertOk()
            ->assertSeeText('Participante Sem Cartas')
            ->assertSeeText('0 de 3 depoimento(s)');
    }

    public function test_review_print_and_completion_flow(): void
    {
        [$flow, $plainToken, $testimonial] = $this->createDistributedFlow();
        $base = $this->eventUrl('vida-vitoriosa', '/fluxos/'.$plainToken);

        $this->get($base)->assertOk()->assertSeeText('Revisar cartas');
        $this->post($base.'/cartas/'.$testimonial->id, ['decision' => 'rejected'])
            ->assertSessionHasErrors('rejection_reason');
        $this->post($base.'/cartas/'.$testimonial->id, [
            'decision' => 'approved',
            'rejection_reason' => '',
        ])->assertRedirect();

        $this->post($base.'/concluir-revisao')->assertRedirect();
        $flow->refresh();
        $this->assertSame('ready_to_print', $flow->status);

        $this->get($base.'/imprimir')
            ->assertOk()
            ->assertSeeText($testimonial->sender_name)
            ->assertSeeText('1 página(s)');

        $this->post($base.'/concluir', ['printed_confirmation' => '1'])->assertRedirect();
        $this->assertDatabaseHas('print_flows', ['id' => $flow->id, 'status' => 'completed']);
        $this->assertDatabaseHas('print_flow_audits', ['print_flow_id' => $flow->id, 'action' => 'print_completed']);
    }

    public function test_selected_subset_is_attached_and_tampered_letter_is_rejected(): void
    {
        $event = $this->useEvent('vida-vitoriosa');
        $participant = Participant::create(['name' => 'Participante A', 'status' => 'active']);
        $otherParticipant = Participant::create(['name' => 'Participante B', 'status' => 'active']);
        $first = $this->testimonial($participant, 'Primeira carta');
        $second = $this->testimonial($participant, 'Segunda carta');
        $foreign = $this->testimonial($otherParticipant, 'Carta de outro participante');
        $member = $this->member($event);
        $request = Request::create($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), 'POST');

        $result = app(PrintFlowManager::class)->distribute([
            'participant_id' => $participant->id,
            'team_member_id' => $member->id,
            'type' => 'main_print',
            'testimonial_ids' => [$first->id],
        ], User::factory()->create()->id, $request);

        $this->assertTrue($result['flow']->testimonials()->whereKey($first->id)->exists());
        $this->assertFalse($result['flow']->testimonials()->whereKey($second->id)->exists());

        try {
            app(PrintFlowManager::class)->distribute([
                'participant_id' => $participant->id,
                'team_member_id' => $member->id,
                'type' => 'main_print',
                'testimonial_ids' => [$foreign->id],
            ], User::factory()->create()->id, $request);
            $this->fail('A carta de outro participante deveria ser rejeitada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('testimonial_ids', $exception->errors());
        }
    }

    public function test_reevaluated_rejected_letter_leaves_automatic_queue_but_remains_available_manually_with_history(): void
    {
        [$mainFlow, , $testimonial] = $this->createDistributedFlow();
        $mainFlow->update(['status' => 'completed', 'current_step' => 'complete', 'completed_at' => now()]);
        PrintFlowReview::create([
            'print_flow_id' => $mainFlow->id,
            'testimonial_id' => $testimonial->id,
            'team_member_id' => $mainFlow->team_member_id,
            'decision' => 'rejected',
            'rejection_reason' => 'Primeira reprovação',
            'decided_at' => now(),
        ]);

        $request = Request::create($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), 'POST');
        $reevaluation = app(PrintFlowManager::class)->distribute([
            'participant_id' => $mainFlow->participant_id,
            'team_member_id' => $mainFlow->team_member_id,
            'type' => 'reevaluation',
            'testimonial_ids' => [$testimonial->id],
        ], User::factory()->create()->id, $request)['flow'];
        $reevaluation->update(['status' => 'completed', 'current_step' => 'complete', 'completed_at' => now()]);
        PrintFlowReview::create([
            'print_flow_id' => $reevaluation->id,
            'testimonial_id' => $testimonial->id,
            'team_member_id' => $reevaluation->team_member_id,
            'decision' => 'rejected',
            'rejection_reason' => 'Ainda precisa de ajuste',
            'decided_at' => now()->addSecond(),
        ]);

        $automatic = app(PrintFlowCandidateService::class)->options('reevaluation');
        $manual = app(PrintFlowCandidateService::class)->options('reevaluation', true);

        $this->assertCount(0, $automatic['participants']);
        $this->assertCount(1, $manual['participants']);
        $letter = $manual['participants']->first()['testimonials']->first();
        $this->assertSame(2, $letter['review_count']);
        $this->assertSame(1, $letter['reevaluation_count']);
        $this->assertSame('Já reavaliada 1 vez(es)', $letter['review_state']);
        $this->assertSame($mainFlow->teamMember->name, $letter['last_reviewer']);
    }

    public function test_main_and_review_cards_use_eligible_participant_and_letter_counts(): void
    {
        $event = $this->useEvent('vida-vitoriosa');
        $participantMain = Participant::create(['name' => 'Para impressão', 'status' => 'active']);
        $this->testimonial($participantMain, 'Carta 1');
        $this->testimonial($participantMain, 'Carta 2');

        $participantReview = Participant::create(['name' => 'Para revisão', 'status' => 'active']);
        $reviewLetter = $this->testimonial($participantReview, 'Carta reprovada');
        $member = $this->member($event);
        $oldFlow = PrintFlow::create([
            'participant_id' => $participantReview->id,
            'team_member_id' => $member->id,
            'type' => 'main_print',
            'status' => 'completed',
            'current_step' => 'complete',
            'distributed_at' => now(),
            'completed_at' => now(),
        ]);
        $oldFlow->testimonials()->attach($reviewLetter->id, ['event_id' => $event->id]);
        PrintFlowReview::create([
            'print_flow_id' => $oldFlow->id,
            'testimonial_id' => $reviewLetter->id,
            'team_member_id' => $member->id,
            'decision' => 'rejected',
            'rejection_reason' => 'Revisar',
            'decided_at' => now(),
        ]);

        $dashboard = app(PrintFlowCandidateService::class)->dashboardData();

        $this->assertSame(1, $dashboard['main_candidates_count']);
        $this->assertSame(2, $dashboard['main_letters_count']);
        $this->assertSame(1, $dashboard['review_candidates_count']);
        $this->assertSame(1, $dashboard['review_letters_count']);
    }

    public function test_multiple_status_filter_uses_union_and_combines_with_participant(): void
    {
        $event = $this->useEvent('vida-vitoriosa');
        $admin = $this->admin();
        $member = $this->member($event);
        $participant = Participant::create(['name' => 'Participante filtrado', 'status' => 'active']);
        $otherParticipant = Participant::create(['name' => 'Participante fora', 'status' => 'active']);

        foreach ([
            [$participant, 'distributed'],
            [$participant, 'ready_to_print'],
            [$participant, 'completed'],
            [$otherParticipant, 'distributed'],
        ] as [$flowParticipant, $status]) {
            PrintFlow::create([
                'participant_id' => $flowParticipant->id,
                'team_member_id' => $member->id,
                'type' => 'testimonial_search',
                'status' => $status,
                'current_step' => 'search',
                'distributed_at' => now(),
                'completed_at' => $status === 'completed' ? now() : null,
            ]);
        }

        $query = http_build_query([
            'participant_id' => $participant->id,
            'status' => ['distributed', 'ready_to_print'],
        ]);

        $response = $this->actingAs($admin)
            ->get($this->eventUrl('vida-vitoriosa', '/admin/print-flows?'.$query));

        $response->assertOk()
            ->assertSeeText('2 status selecionados')
            ->assertViewHas('flows', function ($flows) use ($participant): bool {
                return $flows->count() === 2
                    && $flows->every(fn (PrintFlow $flow): bool => $flow->participant_id === $participant->id)
                    && $flows->pluck('status')->sort()->values()->all() === ['distributed', 'ready_to_print'];
            });
    }

    public function test_share_link_is_visible_once_and_renewal_invalidates_previous_token(): void
    {
        $event = $this->useEvent('vida-vitoriosa');
        $admin = $this->admin();
        $participant = Participant::create(['name' => 'Participante compartilhado', 'status' => 'active']);
        $testimonial = $this->testimonial($participant, 'Carta compartilhada');
        $member = $this->member($event);

        $response = $this->actingAs($admin)->post($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), [
            'participant_id' => $participant->id,
            'team_member_id' => $member->id,
            'type' => 'main_print',
            'testimonial_ids' => [$testimonial->id],
        ]);
        $flow = PrintFlow::firstOrFail();
        $shareUrl = $this->eventUrl('vida-vitoriosa', "/admin/print-flows/{$flow->id}/share");

        $response->assertRedirect($shareUrl);
        $this->get($shareUrl)
            ->assertOk()
            ->assertSeeText('Link temporário disponível')
            ->assertSeeText('Abrir WhatsApp Web');
        $this->get($shareUrl)
            ->assertOk()
            ->assertSeeText('O link original não está mais disponível');

        $oldTokenId = $flow->tokens()->firstOrFail()->id;
        $this->post($this->eventUrl('vida-vitoriosa', "/admin/print-flows/{$flow->id}/renew"))
            ->assertRedirect($shareUrl);
        $this->assertDatabaseMissing('print_flow_tokens', ['id' => $oldTokenId, 'invalidated_at' => null]);
        $this->assertSame(2, $flow->tokens()->count());
    }

    private function createDistributedFlow(): array
    {
        $event = $this->useEvent('vida-vitoriosa');
        $participant = Participant::create(['name' => 'Pessoa do Fluxo', 'status' => 'active']);
        $testimonial = $this->testimonial($participant, 'Autor da Carta');
        $member = $this->member($event, 'Operador', 10);
        $request = Request::create($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), 'POST');
        $result = app(PrintFlowManager::class)->distribute([
            'participant_id' => $participant->id,
            'team_member_id' => $member->id,
            'type' => 'main_print',
            'testimonial_ids' => [$testimonial->id],
        ], User::factory()->create()->id, $request);
        $plainToken = basename(parse_url($result['access_url'], PHP_URL_PATH));

        return [$result['flow'], $plainToken, $testimonial];
    }

    private function testimonial(Participant $participant, string $sender): Testimonial
    {
        return Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => $sender,
            'phone' => '+5521999991234',
            'relationship' => 'Amigo',
            'message' => 'Mensagem curta para impressão.',
            'status' => 'approved',
        ]);
    }

    private function member(Event $event, string $name = 'Membro disponível', ?int $limit = 10): TeamMember
    {
        $member = TeamMember::create([
            'name' => $name,
            'phone' => '+552198888'.str_pad((string) TeamMember::count(), 4, '0', STR_PAD_LEFT),
            'status' => 'active',
            'task_limit' => $limit,
        ]);
        $member->events()->attach($event->id, ['is_active' => true]);

        return $member;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }
}
