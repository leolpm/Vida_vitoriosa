<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Participant;
use App\Models\PrintFlow;
use App\Models\PrintFlowAudit;
use App\Models\PrintFlowReview;
use App\Models\PrintFlowToken;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\CurrentEvent;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

class PrintFlowScenarioSeeder extends Seeder
{
    private const EVENT_SLUGS = ['vida-vitoriosa', 'edd'];

    private const FIXTURES = ['portrait.jpg', 'landscape.jpg', 'square.png', 'panorama.webp'];

    private const BASE_DATE = '2026-08-01 09:00:00';

    public function run(): void
    {
        if (! in_array(app()->environment(), ['local', 'testing'], true)) {
            throw new LogicException('O seeder completo do fluxo de impressão só pode ser executado em local ou testing.');
        }

        $events = Event::active()->whereIn('slug', self::EVENT_SLUGS)->get()->keyBy('slug');

        if ($events->count() !== count(self::EVENT_SLUGS)) {
            throw new LogicException('Os eventos Vida Vitoriosa e EDD devem existir antes de executar o seeder de cenários.');
        }

        $adminId = User::query()->where('role', 'admin')->where('is_active', true)->value('id');

        if (! $adminId) {
            throw new LogicException('Cadastre um administrador ativo antes de executar o seeder de cenários.');
        }

        $members = $this->seedMembers($events);

        foreach (self::EVENT_SLUGS as $slug) {
            $event = $events->get($slug);
            app(CurrentEvent::class)->set($event);

            DB::transaction(function () use ($event, $adminId, $members): void {
                $assets = $this->copyFixtures($event);
                $participants = $this->seedParticipants($event);
                $testimonials = $this->seedTestimonials($event, $participants, $assets);
                $this->seedFlows($event, $participants, $testimonials, $members[$event->slug], $adminId);
            });
        }

        $this->command?->info('Cenários completos do Fluxo de Impressão criados para Vida Vitoriosa e EDD.');
    }

    private function seedMembers(Collection $events): array
    {
        $definitions = [
            'shared' => [
                'name' => 'Mariana Operações Demo',
                'phone' => '+5500000003101',
                'events' => self::EVENT_SLUGS,
            ],
            'vida_operator' => [
                'name' => 'Rafael Impressão Vida Demo',
                'phone' => '+5500000003102',
                'events' => ['vida-vitoriosa'],
            ],
            'vida_reviewer' => [
                'name' => 'Lúcia Revisão Vida Demo',
                'phone' => '+5500000003103',
                'events' => ['vida-vitoriosa'],
            ],
            'edd_operator' => [
                'name' => 'Beatriz Impressão EDD Demo',
                'phone' => '+5500000003104',
                'events' => ['edd'],
            ],
            'edd_reviewer' => [
                'name' => 'Caio Revisão EDD Demo',
                'phone' => '+5500000003105',
                'events' => ['edd'],
            ],
        ];

        $created = [];

        foreach ($definitions as $key => $definition) {
            $member = TeamMember::query()->updateOrCreate(
                ['phone' => $definition['phone']],
                ['name' => $definition['name'], 'status' => 'active', 'task_limit' => 20]
            );
            $member->events()->syncWithoutDetaching(collect($definition['events'])->mapWithKeys(
                fn (string $slug): array => [$events[$slug]->id => ['is_active' => true]]
            )->all());
            $created[$key] = $member;
        }

        return [
            'vida-vitoriosa' => [
                'shared' => $created['shared'],
                'operator' => $created['vida_operator'],
                'reviewer' => $created['vida_reviewer'],
            ],
            'edd' => [
                'shared' => $created['shared'],
                'operator' => $created['edd_operator'],
                'reviewer' => $created['edd_reviewer'],
            ],
        ];
    }

    private function copyFixtures(Event $event): array
    {
        $assets = [];

        foreach (self::FIXTURES as $fixture) {
            $source = database_path('seeders/assets/print-flow/'.$fixture);

            if (! is_file($source)) {
                throw new LogicException("Fixture de imagem não encontrada: {$source}");
            }

            $target = "events/{$event->slug}/demo/{$fixture}";
            Storage::disk('public')->put($target, file_get_contents($source));
            $assets[] = [
                'path' => $target,
                'original_name' => $fixture,
                'size' => Storage::disk('public')->size($target),
            ];
        }

        return $assets;
    }

    private function seedParticipants(Event $event): array
    {
        $participants = [];
        $configuration = $this->eventConfiguration($event->slug);

        foreach ($configuration['participants'] as $scenario => $name) {
            $participants[$scenario] = Participant::query()->updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $name,
                    'status' => 'active',
                    'retreat_edition' => $configuration['edition'],
                ]
            );
        }

        return $participants;
    }

    private function seedTestimonials(Event $event, array $participants, array $assets): array
    {
        $definitions = [
            'large_batch' => ['count' => 12, 'statuses' => ['approved'], 'photos' => [1, 4, 7, 10], 'length' => 'medium'],
            'ready_main' => ['count' => 10, 'statuses' => ['approved'], 'photos' => [2, 5, 8], 'length' => 'short'],
            'reevaluation' => ['count' => 6, 'statuses' => ['approved'], 'photos' => [2, 5], 'length' => 'medium'],
            'search_open' => ['count' => 0, 'statuses' => [], 'photos' => [], 'length' => 'short'],
            'empty' => ['count' => 0, 'statuses' => [], 'photos' => [], 'length' => 'short'],
            'below_target' => ['count' => 1, 'statuses' => ['approved'], 'photos' => [], 'length' => 'short'],
            'mixed_statuses' => ['count' => 5, 'statuses' => ['approved', 'received', 'reviewed', 'archived', 'approved'], 'photos' => [2, 4], 'length' => 'medium'],
            'photo_gallery' => ['count' => 8, 'statuses' => ['approved'], 'photos' => [1, 2, 3, 4, 5, 6, 7, 8], 'length' => 'medium'],
            'long_texts' => ['count' => 4, 'statuses' => ['approved'], 'photos' => [1, 3], 'length' => 'long'],
            'common' => ['count' => 5, 'statuses' => ['approved'], 'photos' => [4], 'length' => 'short'],
        ];

        $result = [];
        $sequence = 0;

        foreach ($definitions as $scenario => $definition) {
            $result[$scenario] = collect();

            for ($index = 1; $index <= $definition['count']; $index++) {
                $sequence++;
                $status = $definition['statuses'][($index - 1) % count($definition['statuses'])];
                $photo = in_array($index, $definition['photos'], true)
                    ? $assets[($index - 1) % count($assets)]
                    : null;
                $relationship = $this->relationship($event->slug, $sequence);
                $senderName = $this->senderName($event->slug, $sequence);
                $testimonial = Testimonial::query()->updateOrCreate(
                    [
                        'participant_id' => $participants[$scenario]->id,
                        'sender_name' => $senderName,
                    ],
                    [
                        'phone' => sprintf('+55 00 90000-%04d', $sequence),
                        'relationship' => $relationship,
                        'relationship_other' => $relationship === 'Outro' ? 'Mentor de teste' : null,
                        'message' => $this->message($participants[$scenario]->name, $senderName, $definition['length'], $index),
                        'photo_path' => $photo['path'] ?? null,
                        'photo_original_name' => $photo['original_name'] ?? null,
                        'photo_size' => $photo['size'] ?? null,
                        'status' => $status,
                        'is_pdf_generated' => false,
                        'pdf_generated_at' => null,
                        'pdf_batch_id' => null,
                    ]
                );
                $timestamp = CarbonImmutable::parse(self::BASE_DATE)->addMinutes($sequence * 7);
                $testimonial->forceFill(['created_at' => $timestamp, 'updated_at' => $timestamp])->saveQuietly();
                $result[$scenario]->push($testimonial);
            }
        }

        return $result;
    }

    private function seedFlows(
        Event $event,
        array $participants,
        array $testimonials,
        array $members,
        int $adminId,
    ): void {
        $baseDate = CarbonImmutable::parse(self::BASE_DATE);
        $reevaluationLetters = $testimonials['reevaluation']->values();

        $historicalMain = $this->seedFlow(
            $event,
            $participants['reevaluation'],
            $members['reviewer'],
            'main_print',
            'completed',
            'complete',
            $reevaluationLetters,
            $adminId,
            $baseDate->addDay(),
        );

        foreach ($reevaluationLetters as $index => $testimonial) {
            $this->seedReview(
                $historicalMain,
                $testimonial,
                $members['reviewer'],
                'rejected',
                'A carta precisa de nova conferência antes da impressão.',
                $baseDate->addDay()->addMinutes(($index + 1) * 10),
            );
        }

        $historicalReevaluation = $this->seedFlow(
            $event,
            $participants['reevaluation'],
            $members['operator'],
            'reevaluation',
            'completed',
            'complete',
            $reevaluationLetters->slice(4, 2),
            $adminId,
            $baseDate->addDays(2),
        );
        $this->seedReview(
            $historicalReevaluation,
            $reevaluationLetters[4],
            $members['operator'],
            'rejected',
            'O texto ainda precisa de ajuste e nova validação.',
            $baseDate->addDays(2)->addMinutes(20),
        );
        $this->seedReview(
            $historicalReevaluation,
            $reevaluationLetters[5],
            $members['operator'],
            'approved',
            null,
            $baseDate->addDays(2)->addMinutes(30),
        );

        $mainFlow = $this->seedFlow(
            $event,
            $participants['ready_main'],
            $members['operator'],
            'main_print',
            'distributed',
            'review',
            $testimonials['ready_main'],
            $adminId,
            $baseDate->addDays(3),
        );
        $reevaluationFlow = $this->seedFlow(
            $event,
            $participants['reevaluation'],
            $members['reviewer'],
            'reevaluation',
            'distributed',
            'review',
            $reevaluationLetters->take(2),
            $adminId,
            $baseDate->addDays(3)->addHour(),
        );
        $searchFlow = $this->seedFlow(
            $event,
            $participants['search_open'],
            $members['shared'],
            'testimonial_search',
            'distributed',
            'search',
            collect(),
            $adminId,
            $baseDate->addDays(3)->addHours(2),
        );

        $this->seedToken($mainFlow, "demo-{$event->slug}-impressao-principal");
        $this->seedToken($reevaluationFlow, "demo-{$event->slug}-reavaliacao");
        $this->seedToken($searchFlow, "demo-{$event->slug}-busca-depoimentos");
    }

    private function seedFlow(
        Event $event,
        Participant $participant,
        TeamMember $member,
        string $type,
        string $status,
        string $step,
        Collection $testimonials,
        int $adminId,
        CarbonImmutable $distributedAt,
    ): PrintFlow {
        $completedAt = $status === 'completed' ? $distributedAt->addHours(2) : null;
        $flow = PrintFlow::query()->updateOrCreate(
            [
                'participant_id' => $participant->id,
                'team_member_id' => $member->id,
                'type' => $type,
            ],
            [
                'status' => $status,
                'current_step' => $step,
                'distributed_by' => $adminId,
                'distributed_at' => $distributedAt,
                'completed_at' => $completedAt,
                'cancelled_at' => null,
            ]
        );
        $flow->testimonials()->sync($testimonials->mapWithKeys(
            fn (Testimonial $testimonial): array => [$testimonial->id => ['event_id' => $event->id]]
        )->all());

        $this->seedAudit($flow, 'flow_distributed', 'admin', $adminId, $distributedAt);

        if ($completedAt) {
            $this->seedAudit($flow, 'flow_completed', 'team_member', $member->id, $completedAt);
        }

        return $flow;
    }

    private function seedReview(
        PrintFlow $flow,
        Testimonial $testimonial,
        TeamMember $member,
        string $decision,
        ?string $reason,
        CarbonImmutable $decidedAt,
    ): void {
        PrintFlowReview::query()->updateOrCreate(
            [
                'print_flow_id' => $flow->id,
                'testimonial_id' => $testimonial->id,
            ],
            [
                'team_member_id' => $member->id,
                'decision' => $decision,
                'rejection_reason' => $reason,
                'decided_at' => $decidedAt,
            ]
        );
    }

    private function seedAudit(
        PrintFlow $flow,
        string $action,
        string $actorType,
        ?int $actorId,
        CarbonImmutable $createdAt,
    ): void {
        PrintFlowAudit::query()->updateOrCreate(
            ['print_flow_id' => $flow->id, 'action' => $action],
            [
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'before_data' => null,
                'after_data' => ['seeded_demo' => true],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PrintFlowScenarioSeeder',
                'created_at' => $createdAt,
            ]
        );
    }

    private function seedToken(PrintFlow $flow, string $plainToken): void
    {
        $hash = hash('sha256', $plainToken);
        $flow->tokens()->where('token_hash', '!=', $hash)->delete();

        PrintFlowToken::query()->updateOrCreate(
            ['token_hash' => $hash],
            [
                'print_flow_id' => $flow->id,
                'expires_at' => now()->addDays(7),
                'max_accesses' => 100,
                'accesses_used' => 0,
                'first_accessed_at' => null,
                'last_accessed_at' => null,
                'invalidated_at' => null,
                'invalidation_reason' => null,
            ]
        );
    }

    private function eventConfiguration(string $slug): array
    {
        if ($slug === 'edd') {
            return [
                'edition' => 'EDD 2026',
                'participants' => [
                    'large_batch' => 'Ana Oliveira',
                    'ready_main' => 'André Nascimento',
                    'reevaluation' => 'Beatriz Carvalho',
                    'search_open' => 'Caio Ribeiro',
                    'empty' => 'Débora Martins',
                    'below_target' => 'Elias Monteiro',
                    'mixed_statuses' => 'Fabiana Lopes',
                    'photo_gallery' => 'Gustavo Alves',
                    'long_texts' => 'Helena Duarte',
                    'common' => 'Isaac Ramos',
                ],
            ];
        }

        return [
            'edition' => 'Vida Vitoriosa 2026',
            'participants' => [
                'large_batch' => 'Ana Oliveira',
                'ready_main' => 'Bruno Martins',
                'reevaluation' => 'Carla Menezes',
                'search_open' => 'Daniel Rocha',
                'empty' => 'Ester Almeida',
                'below_target' => 'Felipe Costa',
                'mixed_statuses' => 'Gabriela Souza',
                'photo_gallery' => 'Henrique Lima',
                'long_texts' => 'Isabela Santos',
                'common' => 'João Ferreira',
            ],
        ];
    }

    private function senderName(string $slug, int $sequence): string
    {
        $firstNames = ['Alice', 'Breno', 'Clara', 'Davi', 'Elisa', 'Fábio', 'Giovana', 'Hugo', 'Íris', 'Jonas', 'Karen', 'Lucas'];
        $lastNames = ['Barbosa', 'Campos', 'Dantas', 'Esteves', 'Freitas', 'Gomes', 'Lacerda', 'Moraes', 'Neves', 'Queiroz'];
        $offset = $slug === 'edd' ? 3 : 0;

        return $firstNames[($sequence + $offset - 1) % count($firstNames)].' '
            .$lastNames[(int) floor(($sequence - 1) / count($firstNames)) % count($lastNames)]
            .' Demo '.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
    }

    private function relationship(string $slug, int $sequence): string
    {
        $relationships = $slug === 'edd'
            ? ['Líder', 'Supervisor', 'Pastor', 'Coordenador', 'Outro']
            : ['Mãe', 'Pai', 'Amigo', 'Líder', 'Pastor', 'Cônjuge', 'Outro'];

        return $relationships[($sequence - 1) % count($relationships)];
    }

    private function message(string $participant, string $sender, string $length, int $index): string
    {
        $opening = "Olá, {$participant}! Esta é uma mensagem fictícia escrita por {$sender} para validar o fluxo de impressão. ";
        $short = 'Que este encontro seja marcado por fé, coragem e esperança. Estamos torcendo por você! 🙏💙';

        if ($length === 'short') {
            return $opening.$short;
        }

        $paragraph = 'Sua caminhada inspira quem está por perto. Cada desafio superado mostra perseverança, cuidado e disposição para crescer. Desejamos que estes dias tragam direção, descanso e lembranças especiais, cercadas de pessoas que reconhecem o valor da sua história.';

        if ($length === 'medium') {
            return $opening.$short."\n\n".$paragraph."\n\nCom carinho, esta mensagem celebra você e tudo o que ainda será construído. ✨";
        }

        $repetitions = $index % 2 === 0 ? 22 : 15;
        $sections = [];

        for ($section = 1; $section <= $repetitions; $section++) {
            $sections[] = "Parte {$section}: {$paragraph} Que cada nova etapa seja vivida com serenidade, propósito e gratidão. 🌿";
        }

        return $opening.$short."\n\n".implode("\n\n", $sections)."\n\nCom muito carinho e alegria por sua vida.";
    }
}
