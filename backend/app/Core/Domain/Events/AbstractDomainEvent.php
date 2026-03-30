<?php

namespace App\Core\Domain\Events;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

abstract class AbstractDomainEvent implements DomainEvent
{
    protected readonly CarbonImmutable $occurredAtValue;

    public function __construct(
        protected readonly int|string|null $aggregateIdValue,
        protected readonly array $payloadValue = [],
        protected readonly array $contextValue = [],
    ) {
        $this->occurredAtValue = CarbonImmutable::now();
    }

    public function eventName(): string
    {
        return (string) Str::of(class_basename(static::class))
            ->beforeLast('Event')
            ->snake('.');
    }

    public function aggregateType(): string
    {
        return (string) Str::of(class_basename(static::class))
            ->beforeLast('Event')
            ->snake();
    }

    public function aggregateId(): int|string|null
    {
        return $this->aggregateIdValue;
    }

    public function payload(): array
    {
        return $this->payloadValue;
    }

    public function context(): array
    {
        return $this->contextValue;
    }

    public function occurredAt(): CarbonImmutable
    {
        return $this->occurredAtValue;
    }
}
