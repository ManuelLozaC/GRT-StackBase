<?php

namespace App\Core\Domain\Events;

trait DispatchesDomainEvents
{
    /** @var array<int, DomainEvent> */
    protected array $recordedDomainEvents = [];

    protected function recordDomainEvent(DomainEvent $event): void
    {
        $this->recordedDomainEvents[] = $event;
    }

    /** @return array<int, DomainEvent> */
    public function releaseDomainEvents(): array
    {
        $events = $this->recordedDomainEvents;
        $this->recordedDomainEvents = [];

        return $events;
    }
}
