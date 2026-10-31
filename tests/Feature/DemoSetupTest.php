<?php

namespace Tests\Feature;

use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class DemoSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_demo_seed_rolls_back_and_can_be_retried(): void
    {
        $event = 'eloquent.creating: '.Reservation::class;
        Event::listen($event, static function (): void {
            throw new RuntimeException('Simulated booking failure');
        });

        try {
            $this->seed();
            $this->fail('The simulated booking failure should stop seeding.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated booking failure', $exception->getMessage());
        } finally {
            Event::forget($event);
        }

        foreach (['users', 'properties', 'room_types', 'rooms', 'reservations', 'room_nights', 'payments', 'audit_events'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }

        $this->seed();
        $this->assertDatabaseCount('properties', 2);
        $this->assertDatabaseCount('rooms', 36);
        $this->assertDatabaseCount('reservations', 28);
        $this->assertDatabaseHas('reservations', ['total' => 3774000]);
    }
}
