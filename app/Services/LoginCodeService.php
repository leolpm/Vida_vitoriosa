<?php

namespace App\Services;

use App\Mail\LoginCodeMail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class LoginCodeService
{
    public function send(User $user): string
    {
        $code = $this->generateCode();
        $expiresMinutes = (int) Setting::valueFor('login_code_expires_minutes', config('vida.login_code_expires_minutes'));

        $user->forceFill([
            'login_code_hash' => Hash::make($code),
            'login_code_expires_at' => now()->addMinutes($expiresMinutes),
        ])->save();

        Mail::to($user->email)->send(new LoginCodeMail($user, $code, $expiresMinutes));

        return $code;
    }

    public function verify(User $user, string $code): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if (! $user->login_code_hash || ! $user->login_code_expires_at) {
            return false;
        }

        if ($user->login_code_expires_at->isPast()) {
            return false;
        }

        return Hash::check($code, $user->login_code_hash);
    }

    public function clear(User $user): void
    {
        $user->forceFill([
            'login_code_hash' => null,
            'login_code_expires_at' => null,
            'last_login_at' => now(),
        ])->save();
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
