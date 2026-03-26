@php($retreatName = \App\Models\Setting::valueFor('retreat_name', config('app.name')))
<div style="font-family: Arial, sans-serif; color: #10202c; line-height: 1.6;">
    <h2 style="margin: 0 0 16px;">Código de acesso - {{ $retreatName }}</h2>
    <p>Olá, {{ $user->name }}.</p>
    <p>Use o código abaixo para acessar a área administrativa:</p>
    <div style="font-size: 32px; font-weight: 700; letter-spacing: 0.28em; padding: 18px 24px; background: #f4eee2; border-radius: 14px; display: inline-block;">
        {{ $code }}
    </div>
    <p style="margin-top: 16px;">Esse código expira em {{ $expiresMinutes }} minutos.</p>
    <p style="color: #5b6875;">Se você não solicitou esse acesso, ignore esta mensagem.</p>
</div>
