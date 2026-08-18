<?php

namespace Tests;

use App\Models\Event;
use App\Support\CurrentEvent;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->useEvent('vida-vitoriosa');
    }

    protected function useEvent(string $slug): Event
    {
        $event = Event::query()->where('slug', $slug)->firstOrFail();

        app(CurrentEvent::class)->set($event);

        return $event;
    }

    protected function eventUrl(string $slug, string $path = '/'): string
    {
        $host = $slug === 'edd'
            ? 'edd.atitudelaranja.test'
            : 'vidavitoriosa.atitudelaranja.test';

        return 'http://'.$host.':8888/'.ltrim($path, '/');
    }
}
