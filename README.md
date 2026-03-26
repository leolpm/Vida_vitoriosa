# Vida Vitoriosa

Sistema Laravel para receber depoimentos do retiro e gerar PDFs por participante.

## Requisitos

- PHP 8.3+
- Composer
- SQLite local, ou outro banco compatível com Laravel

## Instalação local

```bash
composer install
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

O comando `php artisan serve` sobe por padrão em `http://127.0.0.1:8888`.
Se quiser mudar isso, ajuste `APP_SERVE_PORT` no `.env`.

## Acesso administrativo

- E-mail padrão: `admin@vidavitoriosa.local`
- O login é feito por e-mail com código de acesso
- Em ambiente local, o código é enviado para o log porque `MAIL_MAILER=log`

## Estrutura principal

- Formulário público de depoimentos em `/`
- Área administrativa em `/admin`
- CRUD de participantes
- CRUD de usuários administrativos
- Listagem e visualização de depoimentos
- Geração de PDF por participante
- Configuração de imagens do site e do PDF
