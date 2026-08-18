<?php

namespace App\Support;

use App\Models\Event;
use LogicException;

class CurrentEvent
{
    private ?Event $event = null;

    public function set(Event $event): void
    {
        $this->event = $event;
    }

    public function clear(): void
    {
        $this->event = null;
    }

    public function has(): bool
    {
        return $this->event !== null;
    }

    public function get(): Event
    {
        return $this->event ?? throw new LogicException('Nenhum evento foi resolvido para esta requisição.');
    }

    public function id(): int
    {
        return $this->get()->getKey();
    }
}
