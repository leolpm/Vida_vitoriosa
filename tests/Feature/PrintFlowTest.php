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
use App\Services\PrintFlowManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PrintFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_distributes_only_to_authorized_member_and_correct_event(): void
    {
        $vida = $this->useEvent('vida-vitoriosa');
        $edd = Event::where('slug', 'edd')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $participant = Participant::create(['name' => 'Participante Vida', 'status' => 'active']);
        Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'Remetente', 'phone' => '+5521999999999', 'relationship' => 'Amigo',
            'message' => 'Carta para revisão.', 'status' => 'received',
        ]);
        $member = TeamMember::create(['name' => 'Somente EDD', 'phone' => '+5521988888888', 'status' => 'active']);
        $member->events()->attach($edd->id, ['is_active' => true]);

        $this->actingAs($admin)
            ->post($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), [
                'participant_id' => $participant->id,
                'team_member_id' => $member->id,
                'type' => 'main_print',
            ])
            ->assertSessionHasErrors('team_member_id');

        $this->assertDatabaseCount('print_flows', 0);

        $member->events()->attach($vida->id, ['is_active' => true]);
        $response = $this->actingAs($admin)
            ->post($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), [
                'participant_id' => $participant->id,
                'team_member_id' => $member->id,
                'type' => 'main_print',
            ]);

        $response->assertRedirect($this->eventUrl('vida-vitoriosa', '/admin/print-flows'));
        $this->assertDatabaseHas('print_flows', ['event_id' => $vida->id, 'participant_id' => $participant->id]);
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

    public function test_member_with_history_in_another_event_cannot_be_deleted(): void
    {
        $vida = $this->useEvent('vida-vitoriosa');
        $edd = Event::where('slug', 'edd')->firstOrFail();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
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
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
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

    public function test_reevaluation_uses_only_latest_rejected_letters(): void
    {
        [$flow, , $testimonial] = $this->createDistributedFlow();
        PrintFlowReview::create([
            'print_flow_id' => $flow->id, 'testimonial_id' => $testimonial->id,
            'team_member_id' => $flow->team_member_id, 'decision' => 'rejected',
            'rejection_reason' => 'Revisar conteúdo', 'decided_at' => now(),
        ]);

        $request = Request::create($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), 'POST');
        $admin = User::factory()->create();
        $result = app(PrintFlowManager::class)->distribute([
            'participant_id' => $flow->participant_id,
            'team_member_id' => $flow->team_member_id,
            'type' => 'reevaluation',
        ], $admin->id, $request);

        $this->assertTrue($result['flow']->testimonials()->whereKey($testimonial->id)->exists());
    }

    private function createDistributedFlow(): array
    {
        $event = $this->useEvent('vida-vitoriosa');
        $participant = Participant::create(['name' => 'Pessoa do Fluxo', 'status' => 'active']);
        $testimonial = Testimonial::create([
            'participant_id' => $participant->id,
            'sender_name' => 'Autor da Carta', 'phone' => '+5521999991234',
            'relationship' => 'Amigo', 'message' => 'Mensagem curta para impressão.', 'status' => 'received',
        ]);
        $member = TeamMember::create(['name' => 'Operador', 'phone' => '+5521988881234', 'status' => 'active', 'task_limit' => 10]);
        $member->events()->attach($event->id, ['is_active' => true]);
        $admin = User::factory()->create();
        $request = Request::create($this->eventUrl('vida-vitoriosa', '/admin/print-flows'), 'POST');
        $result = app(PrintFlowManager::class)->distribute([
            'participant_id' => $participant->id,
            'team_member_id' => $member->id,
            'type' => 'main_print',
        ], $admin->id, $request);
        $plainToken = basename(parse_url($result['access_url'], PHP_URL_PATH));

        return [$result['flow'], $plainToken, $testimonial];
    }
}
