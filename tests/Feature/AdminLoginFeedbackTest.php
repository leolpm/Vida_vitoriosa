<?php

namespace Tests\Feature;

use App\Mail\LoginCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminLoginFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_code_can_only_be_sent_once_during_the_resend_interval(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'email' => 'admin.feedback@example.com',
        ]);

        $firstResponse = $this->post($this->eventUrl('vida-vitoriosa', '/admin/login/enviar-codigo'), [
            'email' => $user->email,
        ]);

        $firstResponse->assertRedirect($this->eventUrl('vida-vitoriosa', '/admin/login/verificar-codigo'));
        Mail::assertSent(LoginCodeMail::class, 1);

        $firstCodeHash = $user->fresh()->login_code_hash;

        $secondResponse = $this->from($this->eventUrl('vida-vitoriosa', '/admin/login'))
            ->post($this->eventUrl('vida-vitoriosa', '/admin/login/enviar-codigo'), [
                'email' => $user->email,
            ]);

        $secondResponse
            ->assertRedirect($this->eventUrl('vida-vitoriosa', '/admin/login'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'Aguarde')
                && str_contains($message, 'segundos'));

        Mail::assertSent(LoginCodeMail::class, 1);
        $this->assertSame($firstCodeHash, $user->fresh()->login_code_hash);

        $this->travel(61)->seconds();

        $this->post($this->eventUrl('vida-vitoriosa', '/admin/login/enviar-codigo'), [
            'email' => $user->email,
        ])->assertRedirect($this->eventUrl('vida-vitoriosa', '/admin/login/verificar-codigo'));

        Mail::assertSent(LoginCodeMail::class, 2);
        $this->assertNotSame($firstCodeHash, $user->fresh()->login_code_hash);
    }

    public function test_shared_feedback_component_is_rendered_with_contextual_login_text(): void
    {
        $response = $this->get($this->eventUrl('vida-vitoriosa', '/admin/login'));

        $response->assertOk();
        $response->assertSee('data-request-feedback', false);
        $response->assertSee('data-loading-text="Enviando código..."', false);
    }
}
