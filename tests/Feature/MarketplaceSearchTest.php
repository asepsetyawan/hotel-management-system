<?php

namespace Tests\Feature;

use App\Models\{Property, User};
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function search(array $query = [])
    {
        return $this->get('/?'.http_build_query($query + [
            'check_in' => today()->addDays(10)->toDateString(),
            'check_out' => today()->addDays(12)->toDateString(),
            'adults' => 2, 'children' => 0, 'rooms' => 1,
        ]));
    }

    public function test_destination_and_hotel_names_match_without_case_sensitivity(): void
    {
        $this->search(['city' => 'bAnDuNg'])->assertOk()
            ->assertSee('Sora House')->assertSee('Taman Dago Lodge')->assertDontSee('The Aluna Ubud');
        $this->search(['city' => 'aLuNa'])->assertOk()->assertSee('The Aluna Ubud')->assertDontSee('Sora House');
        Property::where('slug', 'sora-house')->update(['published' => false]);
        $this->search(['city' => 'Sora'])->assertOk()->assertSee('No stays match your search.');
    }

    public function test_available_rooms_must_cover_the_entire_stay_and_group_capacity(): void
    {
        $property = Property::where('slug', 'sora-house')->firstOrFail();
        $property->rooms()->whereNotIn('number', ['101', '107'])->update(['out_of_service' => true]);
        // The remaining rooms hold two and three guests respectively.
        $this->search(['city' => 'Sora', 'adults' => 4, 'children' => 1, 'rooms' => 2])
            ->assertOk()->assertSee('Sora House')->assertSee('Reserve each room separately');
        $this->search(['city' => 'Sora', 'adults' => 4, 'children' => 2, 'rooms' => 2])
            ->assertOk()->assertSee('No stays match your search.');
        $room = $property->rooms()->where('number', '107')->firstOrFail();
        $reservation = app(BookingService::class)->create($property, [
            'room_id' => $room->id, 'guest_name' => 'Availability Test', 'guest_email' => 'availability@example.test',
            'guests' => 2, 'check_in' => today()->addDays(11)->toDateString(),
            'check_out' => today()->addDays(12)->toDateString(),
        ]);
        $this->search(['city' => 'Sora', 'rooms' => 2])->assertOk()->assertSee('No stays match your search.');
        app(BookingService::class)->transition($reservation, 'cancelled');
        $this->search(['city' => 'Sora', 'rooms' => 2])->assertOk()->assertSee('Sora House');
    }

    public function test_search_dates_and_group_are_preserved_in_hotel_links(): void
    {
        $response = $this->search(['city' => 'Sora', 'adults' => 3, 'children' => 1, 'rooms' => 2])->assertOk();
        $response->assertSee('check_in='.today()->addDays(10)->toDateString(), false)
            ->assertSee('adults=3&amp;children=1&amp;rooms=2', false);
        $hotels = $response->viewData('properties');
        $this->assertSame(850000, $hotels->first()->available_min_rate);
        $single = $this->search(['city' => 'Sora', 'adults' => 3])->assertOk()->viewData('properties');
        $this->assertSame(1450000, $single->first()->available_min_rate);
    }

    public function test_invalid_searches_are_rejected(): void
    {
        foreach ([['rooms' => 0], ['rooms' => 3, 'adults' => 2], ['children' => -1], ['city' => ['invalid']],
            ['check_out' => today()->addDays(101)->toDateString()],
            ['check_out' => today()->addDays(10)->toDateString()]] as $invalid) {
            $this->getJson('/?'.http_build_query($invalid + [
                'check_in' => today()->addDays(10)->toDateString(), 'adults' => 2,
            ]))->assertUnprocessable();
        }
    }

    public function test_seeding_adds_missing_hotels_without_overwriting_existing_records(): void
    {
        $hotel = Property::where('slug', 'sora-house')->firstOrFail();
        $hotel->update(['tagline' => 'Edited by the owner']);
        $user = User::where('email', 'admin@stayos.test')->firstOrFail();
        $password = $user->password;
        $this->seed();
        $this->assertSame('Edited by the owner', $hotel->fresh()->tagline);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertDatabaseCount('properties', 10);
        $this->assertDatabaseCount('rooms', 180);
        $this->assertDatabaseCount('reservations', 140);
    }
}
