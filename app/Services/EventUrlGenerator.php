<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventDomain;
use Illuminate\Http\Request;

class EventUrlGenerator
{
    public function __construct(private readonly Request $request)
    {
    }

    public function forEvent(Event $event, ?string $path = null): ?string
    {
        $domain = $event->domains()
            ->where('environment', config('events.environment'))
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->first();

        if (! $domain) {
            return null;
        }

        return $this->domainBaseUrl($domain) . '/' . ltrim($path ?? $this->request->path(), '/');
    }

    private function domainBaseUrl(EventDomain $domain): string
    {
        $port = $domain->port && ! in_array([$domain->scheme, $domain->port], [['http', 80], ['https', 443]], true)
            ? ':' . $domain->port
            : '';

        return $domain->scheme . '://' . $domain->host . $port;
    }
}
