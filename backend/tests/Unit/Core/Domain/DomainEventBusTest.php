<?php

namespace Tests\Unit\Core\Domain;

use App\Core\Domain\Events\AbstractDomainEvent;
use App\Core\Domain\Events\DispatchesDomainEvents;
use App\Core\Domain\Events\DomainEventBus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DomainEventBusTest extends TestCase
{
    public function test_it_dispatches_domain_events_through_laravel_dispatcher(): void
    {
        Event::fake();

        $event = new class (42, ['status' => 'created'], ['tenant_id' => 5]) extends AbstractDomainEvent {
        };

        app(DomainEventBus::class)->dispatch($event);

        Event::assertDispatched($event::class);
    }

    public function test_it_can_record_and_release_domain_events(): void
    {
        $aggregate = new class {
            use DispatchesDomainEvents;

            public function create(): void
            {
                $this->recordDomainEvent(new class (10, ['name' => 'Lead A']) extends AbstractDomainEvent {
                });
            }
        };

        $aggregate->create();

        $events = $aggregate->releaseDomainEvents();

        $this->assertCount(1, $events);
        $this->assertSame([], $aggregate->releaseDomainEvents());
        $this->assertSame(10, $events[0]->aggregateId());
    }
}
