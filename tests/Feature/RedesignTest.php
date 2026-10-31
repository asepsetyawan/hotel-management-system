<?php
namespace Tests\Feature;

use App\Models\{Property,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_product_page_is_public_and_labels_reference_pricing(): void
    {
        $this->get('/product')->assertOk()->assertSee('Illustrative plans and prices only.')
            ->assertSee('Fictional demonstration property and reservations');
        $this->get('/')->assertOk()->assertSee('The Aluna Ubud');
    }

    public function test_dashboard_series_are_property_scoped_and_switch_period(): void
    {
        $owner=User::where('role','owner')->firstOrFail();
        $property=Property::where('slug','the-aluna-ubud')->firstOrFail();
        $foreign=Property::where('slug','sora-house')->firstOrFail();
        $foreign->reservations()->firstOrFail()->update(['source'=>'foreign-only-source']);
        $property->reservations()->firstOrFail()->update(['source'=>'own-property-source']);
        $this->actingAs($owner)->withSession(['property_id'=>$property->id]);
        $month=$this->get('/app')->assertOk()->assertDontSee('Foreign-only-source');
        $series=$month->viewData('dashboard');
        $this->assertSame('month',$series['period']);
        $this->assertCount(4,$series['arrivalSeries']);
        $this->assertCount(14,$series['sourceSeries']);
        $this->assertTrue($series['sources']->contains('own-property-source'));
        $year=$this->get('/app?period=year')->assertOk()->viewData('dashboard');
        $this->assertSame(['Q1','Q2','Q3','Q4'],$year['arrivalSeries']->pluck('label')->all());
        $expected=$property->reservations()->where('status','!=','cancelled')->whereYear('check_in',today()->year)->count();
        $this->assertSame($expected,$year['arrivalSeries']->sum('current'));
    }

    public function test_empty_dashboard_explains_how_to_add_data(): void
    {
        $owner=User::where('role','owner')->firstOrFail();
        $empty=Property::create(['name'=>'Empty property','slug'=>'empty-property','city'=>'Test city','address'=>'Test address','email'=>'property@example.test','phone'=>'000','description'=>'Test','tagline'=>'Test']);
        $owner->properties()->attach($empty);
        $this->actingAs($owner)->withSession(['property_id'=>$empty->id])->get('/app')
            ->assertOk()->assertSee('No reservations created in the last 14 days.')
            ->assertSee('No rooms yet. Add a room to see its status.');
    }
}
