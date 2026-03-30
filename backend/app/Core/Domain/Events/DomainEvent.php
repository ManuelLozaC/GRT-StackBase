<?php

namespace App\Core\Domain\Events;

use Carbon\CarbonImmutable;

interface DomainEvent
{
    public function eventName(): string;

    public function aggregateType(): string;

    public function aggregateId(): int|string|null;

    public function payload(): array;

    public function context(): array;

    public function occurredAt(): CarbonImmutable;
}
