<?php
namespace App\Http\Controllers;
use App\Models\{Property,Reservation,Room,RoomType,Payment,AuditEvent};
use App\Services\BookingService;
use App\Domain\Stay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
class HotelController {
 public function __construct(private BookingService $booking){}
 private function property(Request $request):Property {
  $query=$request->user()->properties();
  $id=$request->session()->get('property_id');
  $property=$id?$query->where('properties.id',$id)->first():$query->orderBy('properties.id')->first();
  abort_unless($property,403,'No assigned property. Ask your administrator for access.');return $property;
 }
 private function frontdesk(Request $request):void {abort_unless(in_array($request->user()->role,['owner','manager','frontdesk'],true),403);}
 private function manager(Request $request):void {abort_unless($request->user()->canManage(),403);}
 public function select(Request $request){$data=$request->validate(['property_id'=>'required|integer']);abort_unless($request->user()->properties()->where('properties.id',$data['property_id'])->exists(),403);$request->session()->put('property_id',$data['property_id']);return redirect('/app');}
 public function page(Request $request,string $page='overview'){
  abort_unless(in_array($page,['overview','reservations','calendar','rooms','guests','housekeeping','billing','reports','channels','website','mobile','settings']),404);
  if($request->user()->role==='housekeeping'&&!in_array($page,['housekeeping','mobile']))return redirect('/app/housekeeping');
  if(in_array($page,['channels','website','settings']))$this->manager($request);
  $property=$this->property($request);$properties=$request->user()->properties()->get();
  $rooms=$property->rooms()->with('type')->orderBy('number')->get();$types=$property->roomTypes()->get();
  $reservations=$property->reservations()->with(['room.type','payments'])->orderByDesc('created_at');
  if($request->filled('q')){$q=substr($request->string('q')->toString(),0,100);$reservations->where(fn($s)=>$s->where('guest_name','like',"%$q%")->orWhere('reference','like',"%$q%"));}
  if(in_array($request->input('status'),['confirmed','checked_in','checked_out','cancelled']))$reservations->where('status',$request->input('status'));
  $reservations=$reservations->paginate(15)->withQueryString();
  $today=today();$inHouse=$property->reservations()->where('status','checked_in')->count();
  $arrivals=$property->reservations()->with('room')->whereDate('check_in',$today)->where('status','confirmed')->get();
  $departures=$property->reservations()->with('room')->whereDate('check_out','<=',$today)->where('status','checked_in')->get();
  $revenue=(int)Payment::whereHas('reservation',fn($q)=>$q->where('property_id',$property->id))->whereMonth('created_at',$today->month)->whereYear('created_at',$today->year)->sum('amount');
  $occupancy=$rooms->where('out_of_service',false)->count()?round($inHouse/$rooms->where('out_of_service',false)->count()*100):0;
  $week=collect(range(0,6))->map(function($n)use($property,$today){$date=$today->copy()->addDays($n);$count=$property->reservations()->whereIn('status',['confirmed','checked_in'])->whereDate('check_in','<=',$date)->whereDate('check_out','>',$date)->count();return ['date'=>$date,'count'=>$count];});
  $activity=AuditEvent::where('property_id',$property->id)->with('user')->latest()->limit(6)->get();
  $calendar=$property->reservations()->with('room')->whereIn('status',['confirmed','checked_in'])->whereDate('check_out','>',$today)->whereDate('check_in','<',$today->copy()->addDays(7))->get();
  $guests=$property->reservations()->select('guest_email')->selectRaw('MAX(guest_name) as guest_name, MAX(guest_phone) as guest_phone, COUNT(*) as stays, SUM(total) as booked_value')->groupBy('guest_email')->orderBy('guest_name')->get();
  $payments=Payment::with('reservation')->whereHas('reservation',fn($q)=>$q->where('property_id',$property->id))->latest()->limit(30)->get();
  $occupiedRoomIds=$property->reservations()->where('status','checked_in')->pluck('room_id')->all();
  $stats=compact('inHouse','revenue','occupancy');
  $dashboard=[];
  if($page==='overview'){
   $period=$request->input('period')==='year'?'year':'month';
   $arrivalSeries=collect(range(0,3))->map(function($n)use($property,$today,$period){
    $start=$period==='year'?$today->copy()->startOfYear()->addMonths($n*3):$today->copy()->startOfMonth()->subMonths(3-$n);
    $end=$period==='year'?$start->copy()->addMonths(3):$start->copy()->addMonth();
    $count=fn($from,$to)=>$property->reservations()->where('status','!=','cancelled')->whereDate('check_in','>=',$from)->whereDate('check_in','<',$to)->count();
    return ['label'=>$period==='year'?'Q'.($n+1):$start->format('M'),'current'=>$count($start,$end),'previous'=>$count($start->copy()->subYear(),$end->copy()->subYear()),'date'=>$start->format('M Y')];
   });
   $sourceRecords=$property->reservations()->where('created_at','>=',$today->copy()->subDays(13))->get(['source','created_at']);
   $sources=$sourceRecords->pluck('source')->unique()->values();
   $sourceSeries=collect(range(0,13))->map(function($n)use($today,$sourceRecords,$sources){$date=$today->copy()->subDays(13-$n);return ['date'=>$date,'counts'=>$sources->mapWithKeys(fn($s)=>[$s=>$sourceRecords->filter(fn($r)=>$r->source===$s&&$r->created_at->isSameDay($date))->count()])];});
   $dashboard=compact('period','arrivalSeries','sources','sourceSeries');
  }
  return view('admin.'.$page,compact('property','properties','rooms','types','reservations','arrivals','departures','week','activity','stats','page','calendar','guests','payments','occupiedRoomIds','dashboard'));
 }
 public function reservation(Request $request){
  $this->frontdesk($request);$property=$this->property($request);$data=$this->validateBooking($request);
  $r=$this->booking->create($property,$data,'frontdesk');return redirect('/app/reservations')->with('success',"Reservation {$r->reference} created.");
 }
 public function validateBooking(Request $request):array {
  $data=$request->validate(['room_id'=>'required|integer','guest_name'=>'required|string|max:120','guest_email'=>'required|email|max:180','guest_phone'=>'nullable|string|max:40','guests'=>'required|integer|min:1|max:12','check_in'=>'required|date_format:Y-m-d|after_or_equal:today','check_out'=>'required|date_format:Y-m-d|after:check_in','notes'=>'nullable|string|max:1000']);
  try{Stay::nights($data['check_in'],$data['check_out']);}catch(\InvalidArgumentException $e){throw ValidationException::withMessages(['check_out'=>$e->getMessage()]);}return $data;
 }
 public function status(Request $request,int $id){$this->frontdesk($request);$data=$request->validate(['status'=>'required|in:checked_in,checked_out,cancelled']);$r=$this->property($request)->reservations()->findOrFail($id);$this->booking->transition($r,$data['status']);return back()->with('success','Reservation updated.');}
 public function payment(Request $request,int $id){
  $this->frontdesk($request);$property=$this->property($request);$data=$request->validate(['amount'=>'required|integer|min:1|max:1000000000','method'=>'required|in:cash,bank_transfer,card_terminal','note'=>'nullable|string|max:150','idempotency_key'=>'required|uuid']);
  $r=$property->reservations()->findOrFail($id);
  DB::transaction(function()use($r,$data){$locked=Reservation::lockForUpdate()->with('payments')->findOrFail($r->id);
   if(Payment::where('idempotency_key',$data['idempotency_key'])->exists())return;
   if($locked->status==='cancelled'||$data['amount']>$locked->balance)throw ValidationException::withMessages(['amount'=>'Payment must not exceed the balance, and cancelled bookings cannot receive payments.']);
   Payment::create($data+['reservation_id'=>$locked->id,'user_id'=>auth()->id()]);$this->booking->audit($locked->property_id,'Payment recorded',$locked->reference);
  },5);return back()->with('success','Payment recorded.');
 }
 public function housekeeping(Request $request,int $id){$property=$this->property($request);$room=$property->rooms()->findOrFail($id);$data=$request->validate(['housekeeping_status'=>'required|in:clean,dirty,inspecting']);$room->update($data);$this->booking->audit($property->id,'Housekeeping updated','Room '.$room->number);return back()->with('success','Room status updated.');}
 public function room(Request $request){$this->manager($request);$property=$this->property($request);$data=$request->validate(['number'=>'required|string|max:12','floor'=>'required|string|max:20','room_type_id'=>'required|integer']);$property->roomTypes()->findOrFail($data['room_type_id']);if($property->rooms()->where('number',$data['number'])->exists())throw ValidationException::withMessages(['number'=>'Room number already exists.']);$property->rooms()->create($data+['calendar_token'=>Str::random(48)]);return back()->with('success','Room added.');}
 public function roomService(Request $request,int $id){$this->manager($request);$room=$this->property($request)->rooms()->findOrFail($id);DB::transaction(function()use($room){$room=Room::lockForUpdate()->findOrFail($room->id);if(!$room->out_of_service&&($room->nights()->whereDate('night','>=',today())->exists()||$room->reservations()->where('status','checked_in')->exists()))throw ValidationException::withMessages(['room'=>'Move or cancel upcoming reservations before taking this room out of service.']);$room->update(['out_of_service'=>!$room->out_of_service]);},5);return back()->with('success','Room availability updated.');}
 public function type(Request $request){$this->manager($request);$property=$this->property($request);$data=$request->validate(['name'=>'required|string|max:80','description'=>'required|string|max:500','capacity'=>'required|integer|min:1|max:12','nightly_rate'=>'required|integer|min:1|max:100000000','amenities'=>'required|string|max:200']);$property->roomTypes()->create($data);return back()->with('success','Room type created.');}
 public function rate(Request $request,int $id){$this->manager($request);$data=$request->validate(['nightly_rate'=>'required|integer|min:1|max:100000000']);$this->property($request)->roomTypes()->findOrFail($id)->update($data);return back()->with('success','Rate updated for new bookings. Existing bookings retain their quoted rate.');}
 public function settings(Request $request){$this->manager($request);$data=$request->validate(['name'=>'required|string|max:100','city'=>'required|string|max:100','address'=>'required|string|max:200','tagline'=>'required|string|max:160','description'=>'required|string|max:3000','email'=>'required|email|max:180','phone'=>'required|string|max:40','tax_basis_points'=>'required|integer|min:0|max:10000','published'=>'nullable|boolean']);$data['published']=$request->boolean('published');$this->property($request)->update($data);return back()->with('success','Property settings saved.');}
 public function invoice(Request $request,int $id){$this->frontdesk($request);$reservation=$this->property($request)->reservations()->with(['room.type','property','payments'])->findOrFail($id);return response()->view('admin.invoice',compact('reservation'))->header('Cache-Control','private, no-store');}

 public function createProperty(Request $request){
  abort_unless($request->user()->role==='owner',403);
  $data=$request->validate(['name'=>'required|string|max:100','slug'=>'required|string|max:100|alpha_dash:ascii|unique:properties,slug','city'=>'required|string|max:100','address'=>'required|string|max:200','email'=>'required|email|max:180','phone'=>'required|string|max:40']);
  $property=DB::transaction(function()use($data,$request){$p=Property::create($data+['tagline'=>'Your next memorable stay.','description'=>'Welcome to '.$data['name'].'.','published'=>false]);$request->user()->properties()->attach($p);return $p;});
  $request->session()->put('property_id',$property->id);return redirect('/app/settings')->with('success','Property created as a draft. Add room types and rooms before publishing.');
 }
 public function createStaff(Request $request){
  abort_unless($request->user()->role==='owner',403);$property=$this->property($request);
  $data=$request->validate(['name'=>'required|string|max:100','email'=>'required|email|max:180|unique:users,email','password'=>'required|string|min:12|max:128|confirmed','role'=>'required|in:manager,frontdesk,housekeeping']);
  DB::transaction(function()use($data,$property){$user=\App\Models\User::create(collect($data)->only(['name','email','password','role'])->all());$user->properties()->attach($property);});
  $this->booking->audit($property->id,'Staff account created',$data['name']);return back()->with('success','Staff account created for this property.');
 }
 public function export(Request $request){$this->manager($request);$rows=$this->property($request)->reservations()->with('room')->orderBy('check_in')->get();return response()->streamDownload(function()use($rows){$f=fopen('php://output','w');fputcsv($f,['Reference','Room','Arrival','Departure','Status','Source','Total IDR'],',','"','');foreach($rows as $r)fputcsv($f,array_map(fn($cell)=>preg_match('/^[=+@\\t\\r\\n-]/',(string)$cell)?"'".$cell:$cell,[$r->reference,$r->room->number,$r->check_in->toDateString(),$r->check_out->toDateString(),$r->status,$r->source,$r->total]),',','"','');fclose($f);},'reservations.csv',['Content-Type'=>'text/csv','Cache-Control'=>'no-store']);}
}
