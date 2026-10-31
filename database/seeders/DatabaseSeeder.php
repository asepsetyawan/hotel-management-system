<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{User,Property,RoomType,Room,Reservation,RoomNight,Payment};
use App\Services\BookingService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
class DatabaseSeeder extends Seeder {
 public function run():void {
  if(!app()->environment(['local','testing'])){ $this->command?->warn('Demo data is restricted to local/testing.');return; }
  $password=env('DEMO_ADMIN_PASSWORD');
  if(!$password||strlen($password)<12)throw new \RuntimeException('Set DEMO_ADMIN_PASSWORD to at least 12 characters before seeding.');
  DB::transaction(function()use($password){
  $owner=User::firstOrCreate(['email'=>'admin@stayos.test'],['name'=>'Alex Morgan','password'=>$password,'role'=>'owner']);
  $desk=User::firstOrCreate(['email'=>'desk@stayos.test'],['name'=>'Dina Putri','password'=>$password,'role'=>'frontdesk']);
  $housekeeper=User::firstOrCreate(['email'=>'housekeeping@stayos.test'],['name'=>'Made Ayu','password'=>$password,'role'=>'housekeeping']);
  $booking=app(BookingService::class);
  foreach([['The Aluna Ubud','the-aluna-ubud','Ubud, Bali','A little closer to nature. A little closer to you.','Jl. Bisma No. 18, Ubud, Bali','A quiet retreat among the palms. Slow mornings, thoughtful spaces, and the warmth of Balinese hospitality. Discover a different rhythm at The Aluna.'],['Sora House','sora-house','Bandung','Your quiet corner of the city.','Jl. Riau No. 42, Bandung','An intimate urban hideaway with warm interiors, garden courtyards, and room to unwind. Make yourself at home in the heart of Bandung.'],['Laras Heritage Hotel','laras-heritage-hotel','Yogyakarta','A restful stay near the old city.','Jl. Prawirotaman No. 25, Yogyakarta','A fictional heritage hotel with shaded courtyards, locally inspired interiors, and space for families to gather.'],['Senja Coast Resort','senja-coast-resort','Lombok','Slow days by the coast.','Jl. Pantai Senggigi No. 12, Lombok','A fictional coastal retreat with garden rooms, spacious suites, and private villas for relaxed island holidays.'],['Nusa City Hotel','nusa-city-hotel','Jakarta','Room to rest in the city.','Jl. Cikini Raya No. 36, Jakarta','A fictional city hotel with comfortable rooms and generous suites for business trips and weekend stays.'],['Taman Dago Lodge','taman-dago-lodge','Bandung','A cool hillside escape.','Jl. Dago Atas No. 8, Bandung','A fictional hillside lodge with leafy terraces and comfortable rooms above the city.'],['Aruna Sanur Stay','aruna-sanur-stay','Sanur, Bali','Easy mornings on the island.','Jl. Danau Tamblingan No. 50, Sanur, Bali','A fictional island hotel with garden rooms and space for relaxed family stays.'],['Banyu Lakeside Hotel','banyu-lakeside-hotel','Malang','A little room to breathe.','Jl. Danau No. 17, Malang','A fictional lakeside hotel with airy rooms and quiet outdoor spaces in the highlands.'],['Rimba Bogor Retreat','rimba-bogor-retreat','Bogor','Take the slower road.','Jl. Raya Puncak No. 21, Bogor','A fictional green retreat for weekends among the trees with friends and family.'],['Pelangi Harbour Hotel','pelangi-harbour-hotel','Surabaya','A welcoming base by the harbour.','Jl. Pelabuhan No. 9, Surabaya','A fictional city hotel offering comfortable rooms and suites for short breaks and work trips.']] as $index=>$details){
   [$name,$slug,$city,$tagline,$address,$description]=$details;
   if(Property::where('slug',$slug)->exists())continue;
   $property=Property::create(compact('name','slug','city','tagline','address','description')+['email'=>'hello@'.$slug.'.test','phone'=>'+62 361 123 456','published'=>true]);
   $owner->properties()->attach($property);$desk->properties()->attach($property);
   if($index===0)$housekeeper->properties()->attach($property);
   $types=[];
   foreach([['Garden Deluxe',2,850000,'A sunlit space overlooking the tropical garden.','King bed, Garden view, Wi-Fi, Breakfast'],['Terrace Suite',3,1450000,'Space to slow down, with your own private terrace.','King bed, Private terrace, Bathtub, Breakfast'],['Pool Villa',4,2400000,'A private sanctuary with a plunge pool and open living space.','Private pool, Two bedrooms, Living area, Breakfast']] as $type){
    $types[]=RoomType::create(['property_id'=>$property->id,'name'=>$type[0],'capacity'=>$type[1],'nightly_rate'=>$type[2],'description'=>$type[3],'amenities'=>$type[4]]);
   }
   $rooms=[];
   for($i=0;$i<18;$i++)$rooms[]=Room::create(['property_id'=>$property->id,'room_type_id'=>$types[intdiv($i,6)]->id,'number'=>(string)(101+$i),'floor'=>$i<9?'Ground floor':'First floor','calendar_token'=>Str::random(48),'housekeeping_status'=>$i===16?'dirty':($i===17?'inspecting':'clean')]);
   $names=['Olivia Bennett','Rizky Pratama','Sophie Laurent','James Wilson','Anindya Putri','Noah Williams','Emma Thompson','Dimas Saputra','Charlotte Lee','Liam Anderson','Amelia Clarke','Ethan Walker','Isabella Rossi','Lucas Martin'];
   foreach($names as $n=>$guest){
    $arrival=match(true){$n<7=>today()->subDays(2),$n<10=>today(),$n<12=>today()->addDay(),default=>today()->subDays(5)};
    $departure=match(true){$n<5=>today()->addDays(2),$n<7=>today(),$n<10=>today()->addDays(3),$n<12=>today()->addDays(4),default=>today()->subDays(2)};
    $r=$booking->create($property,['room_id'=>$rooms[$n]->id,'guest_name'=>$guest,'guest_email'=>Str::slug($guest,'.').'@example.test','guest_phone'=>'+62 812 0000 '.str_pad((string)$n,4,'0',STR_PAD_LEFT),'guests'=>2,'check_in'=>$arrival->toDateString(),'check_out'=>$departure->toDateString()],'direct');
    if($n<7)$r->update(['status'=>'checked_in','checked_in_at'=>$arrival->copy()->setTime(14,0)]);
    if($n>=12){$r->update(['status'=>'checked_out','checked_in_at'=>$arrival->copy()->setTime(14,0),'checked_out_at'=>$departure->copy()->setTime(11,0)]);RoomNight::where('reservation_id',$r->id)->delete();$rooms[$n]->update(['housekeeping_status'=>'dirty']);}
    Payment::create(['reservation_id'=>$r->id,'user_id'=>$owner->id,'amount'=>$n<7||$n>=12?$r->total:(int)round($r->total/2),'method'=>$n%2?'bank_transfer':'cash','idempotency_key'=>(string)Str::uuid(),'note'=>'Local demo payment']);
   }
  }
  });
 }
}
