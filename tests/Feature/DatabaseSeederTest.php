<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventSetting;
use App\Models\Participant;
use App\Models\PrintFlow;
use App\Models\PrintFlowAudit;
use App\Models\PrintFlowReview;
use App\Models\PrintFlowToken;
use App\Models\TeamMember;
use App\Models\Testimonial;
use App\Models\User;
use App\Services\PrintFlowCandidateService;
use App\Services\PrintPageComposer;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PrintFlowScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_complete_isolated_scenario_matrix_for_both_events(): void
    {
        $this->seedDemoDatabase();

        $vida = Event::where('slug', 'vida-vitoriosa')->firstOrFail();
        $edd = Event::where('slug', 'edd')->firstOrFail();

        foreach ([$vida, $edd] as $event) {
            $this->assertSame(10, Participant::withoutGlobalScopes()
                ->where('event_id', $event->id)
                ->where('status', 'active')
                ->count());
            $this->assertSame(51, Testimonial::withoutGlobalScopes()->where('event_id', $event->id)->count());
        }

        $this->assertSame(2, Participant::withoutGlobalScopes()->where('name', 'Ana Oliveira')->count());
        $this->assertParticipantLetterCount($vida, 'Ana Oliveira', 12);
        $this->assertParticipantLetterCount($vida, 'Daniel Rocha', 0);
        $this->assertParticipantLetterCount($vida, 'Ester Almeida', 0);
        $this->assertParticipantLetterCount($vida, 'Felipe Costa', 1);
        $this->assertParticipantLetterCount($edd, 'Ana Oliveira', 12);
        $this->assertParticipantLetterCount($edd, 'Caio Ribeiro', 0);
        $this->assertParticipantLetterCount($edd, 'Débora Martins', 0);
        $this->assertParticipantLetterCount($edd, 'Elias Monteiro', 1);

        $this->assertSame(
            'Envie uma mensagem especial para seu liderado',
            EventSetting::where('event_id', $edd->id)->where('key', 'form_title')->value('value')
        );

        $admin = User::where('email', 'leolpm2@hotmail.com')->firstOrFail();
        $this->assertSame(2, $admin->events()->wherePivot('is_active', true)->count());
    }

    public function test_seeder_copies_valid_images_with_metadata_formats_and_orientations_per_event(): void
    {
        $this->seedDemoDatabase();

        foreach (Event::whereIn('slug', ['vida-vitoriosa', 'edd'])->get() as $event) {
            $photos = Testimonial::withoutGlobalScopes()
                ->where('event_id', $event->id)
                ->whereNotNull('photo_path')
                ->get();
            $extensions = [];
            $orientations = [];

            $this->assertGreaterThanOrEqual(15, $photos->count());

            foreach ($photos as $photo) {
                $this->assertStringStartsWith("events/{$event->slug}/demo/", $photo->photo_path);
                Storage::disk('public')->assertExists($photo->photo_path);
                $this->assertSame(Storage::disk('public')->size($photo->photo_path), (int) $photo->photo_size);

                $path = Storage::disk('public')->path($photo->photo_path);
                [$width, $height] = getimagesize($path);
                $extensions[] = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $orientations[] = $width === $height ? 'square' : ($width > $height ? 'landscape' : 'portrait');
            }

            $this->assertEqualsCanonicalizing(['jpg', 'png', 'webp'], array_values(array_unique($extensions)));
            $this->assertEqualsCanonicalizing(['landscape', 'portrait', 'square'], array_values(array_unique($orientations)));
        }
    }

    public function test_seeded_flows_cover_main_reevaluation_manual_and_search_queues(): void
    {
        $this->seedDemoDatabase();

        $scenarios = [
            'vida-vitoriosa' => [
                'large' => 'Ana Oliveira',
                'ready' => 'Bruno Martins',
                'reevaluation' => 'Carla Menezes',
                'search_open' => 'Daniel Rocha',
                'empty' => 'Ester Almeida',
                'below' => 'Felipe Costa',
            ],
            'edd' => [
                'large' => 'Ana Oliveira',
                'ready' => 'André Nascimento',
                'reevaluation' => 'Beatriz Carvalho',
                'search_open' => 'Caio Ribeiro',
                'empty' => 'Débora Martins',
                'below' => 'Elias Monteiro',
            ],
        ];

        foreach ($scenarios as $slug => $names) {
            $event = $this->useEvent($slug);
            $service = app(PrintFlowCandidateService::class);

            $this->assertSame(3, PrintFlow::whereIn('status', PrintFlow::OPEN_STATUSES)->count());
            $this->assertSame(3, PrintFlowToken::whereHas('printFlow', fn ($query) => $query->where('event_id', $event->id))->count());

            foreach (['impressao-principal', 'reavaliacao', 'busca-depoimentos'] as $suffix) {
                $token = "demo-{$slug}-{$suffix}";
                $this->assertDatabaseHas('print_flow_tokens', [
                    'token_hash' => hash('sha256', $token),
                    'max_accesses' => 100,
                    'accesses_used' => 0,
                ]);
            }

            $main = $service->options('main_print')['participants'];
            $large = $main->firstWhere('name', $names['large']);
            $this->assertNotNull($large);
            $this->assertSame(12, $large['eligible_count']);
            $this->assertNull($main->firstWhere('name', $names['ready']));

            $automatic = $service->options('reevaluation')['participants'];
            $manual = $service->options('reevaluation', true)['participants'];
            $this->assertSame(2, $automatic->firstWhere('name', $names['reevaluation'])['eligible_count']);
            $this->assertSame(3, $manual->firstWhere('name', $names['reevaluation'])['eligible_count']);

            $search = $service->options('testimonial_search')['participants'];
            $this->assertNull($search->firstWhere('name', $names['search_open']));
            $this->assertNotNull($search->firstWhere('name', $names['empty']));
            $this->assertNotNull($search->firstWhere('name', $names['below']));

            $readyParticipant = Participant::where('name', $names['ready'])->firstOrFail();
            $readyFlow = PrintFlow::where('participant_id', $readyParticipant->id)
                ->where('type', 'main_print')
                ->where('status', 'distributed')
                ->firstOrFail();
            $this->assertSame(10, $readyFlow->testimonials()->count());

            $reevaluationParticipant = Participant::where('name', $names['reevaluation'])->firstOrFail();
            $reviewCounts = $reevaluationParticipant->testimonials()
                ->withCount('printFlowReviews')
                ->pluck('print_flow_reviews_count')
                ->sort()
                ->values()
                ->all();
            $this->assertSame([1, 1, 1, 1, 2, 2], $reviewCounts);
            $this->assertSame(2, PrintFlowReview::whereIn('testimonial_id', $reevaluationParticipant->testimonials()->pluck('id'))
                ->distinct()
                ->count('team_member_id'));
        }
    }

    public function test_long_letters_with_and_without_images_create_multiple_pages(): void
    {
        $this->seedDemoDatabase();

        foreach (['vida-vitoriosa' => 'Isabela Santos', 'edd' => 'Helena Duarte'] as $slug => $participantName) {
            $this->useEvent($slug);
            $participant = Participant::where('name', $participantName)->firstOrFail();
            $result = app(PrintPageComposer::class)->compose($participant->testimonials()->orderBy('id')->get());
            $withPhoto = collect($result['letters'])->filter(fn (array $letter): bool => (bool) $letter['testimonial']->photo_path);
            $withoutPhoto = collect($result['letters'])->reject(fn (array $letter): bool => (bool) $letter['testimonial']->photo_path);

            $this->assertNotEmpty($withPhoto);
            $this->assertNotEmpty($withoutPhoto);
            $this->assertTrue($withPhoto->every(fn (array $letter): bool => $letter['page_count'] > 1));
            $this->assertTrue($withoutPhoto->every(fn (array $letter): bool => $letter['page_count'] > 1));
        }
    }

    public function test_complete_scenario_seeder_is_idempotent(): void
    {
        $this->seedDemoDatabase();
        $before = $this->scenarioCounts();

        $this->seed(PrintFlowScenarioSeeder::class);

        $this->assertSame($before, $this->scenarioCounts());
        $this->assertSame([
            'participants' => 20,
            'testimonials' => 102,
            'team_members' => 5,
            'flows' => 10,
            'reviews' => 16,
            'tokens' => 6,
            'audits' => 14,
        ], $before);
    }

    public function test_complete_database_seeder_refuses_production_before_writing_data(): void
    {
        $originalEnvironment = app()->environment();

        try {
            app()->instance('env', 'production');
            (new DatabaseSeeder)->run();
            $this->fail('O DatabaseSeeder deveria recusar execução em produção.');
        } catch (LogicException $exception) {
            $this->assertSame(
                'O DatabaseSeeder de demonstração só pode ser executado em local ou testing.',
                $exception->getMessage()
            );
        } finally {
            app()->instance('env', $originalEnvironment);
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseCount('testimonials', 0);
    }

    private function seedDemoDatabase(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        Storage::disk('public')->assertExists('events/edd/settings/public-site-default.png');
        Storage::disk('public')->assertExists('events/edd/settings/pdf-header-default.png');
        Storage::disk('public')->assertExists('events/vida-vitoriosa/settings/public-site-default.png');
        Storage::disk('public')->assertExists('events/vida-vitoriosa/settings/pdf-header-default.png');
    }

    private function assertParticipantLetterCount(Event $event, string $name, int $expected): void
    {
        $participant = Participant::withoutGlobalScopes()
            ->where('event_id', $event->id)
            ->where('name', $name)
            ->firstOrFail();

        $this->assertSame($expected, Testimonial::withoutGlobalScopes()->where('participant_id', $participant->id)->count());
    }

    private function scenarioCounts(): array
    {
        return [
            'participants' => Participant::withoutGlobalScopes()->count(),
            'testimonials' => Testimonial::withoutGlobalScopes()->count(),
            'team_members' => TeamMember::count(),
            'flows' => PrintFlow::withoutGlobalScopes()->count(),
            'reviews' => PrintFlowReview::withoutGlobalScopes()->count(),
            'tokens' => PrintFlowToken::count(),
            'audits' => PrintFlowAudit::withoutGlobalScopes()->count(),
        ];
    }
}
