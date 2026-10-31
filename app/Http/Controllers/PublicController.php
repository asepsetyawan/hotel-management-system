<?php
namespace App\Http\Controllers;
use App\Models\{Property,Reservation,Room};
use App\Services\BookingService;
use App\Domain\Stay;
use Illuminate\Http\Request;
class PublicController {
 public function marketplace(Request $request){
  $data=$request->validate([
   'city'=>'nullable|string|max:80','check_in'=>'nullable|date_format:Y-m-d|after_or_equal:today',
   'check_out'=>'nullable|date_format:Y-m-d|after:check_in','adults'=>'nullable|integer|min:1|max:24',
   'children'=>'nullable|integer|min:0|max:12','rooms'=>'nullable|integer|min:1|max:8',
  ]);
  $search=['city'=>trim($data['city']??''),'check_in'=>$data['check_in']??today()->toDateString(),
   'check_out'=>$data['check_out']??\Carbon\Carbon::parse($data['check_in']??today())->addDay()->toDateString(),
   'adults'=>(int)($data['adults']??2),'children'=>(int)($data['children']??0),'rooms'=>(int)($data['rooms']??1)];
  try{$nights=Stay::nights($search['check_in'],$search['check_out']);}
  catch(\InvalidArgumentException $e){throw \Illuminate\Validation\ValidationException::withMessages(['check_out'=>$e->getMessage()]);}
  if($search['adults']<$search['rooms'])throw \Illuminate\Validation\ValidationException::withMessages(['adults'=>'Choose at least one adult per room.']);
  $q=Property::where('published',true)->with(['rooms'=>fn($q)=>$q->with('type')->where('out_of_service',false)
   ->whereDoesntHave('nights',fn($q)=>$q->whereIn('night',$nights))]);
  if($search['city']!=='')$q->where(fn($q)=>$q->whereLike('city','%'.$search['city'].'%')->orWhereLike('name','%'.$search['city'].'%'));
  $properties=$q->orderBy('id')->get()->filter(function($property)use($search){
   $available=$property->rooms;
   $capacity=$available->sortByDesc(fn($room)=>$room->type->capacity)->take($search['rooms'])->sum(fn($room)=>$room->type->capacity);
   if($available->count()<$search['rooms']||$capacity<$search['adults']+$search['children'])return false;
   $eligible=$search['rooms']===1?$available->filter(fn($room)=>$room->type->capacity>=$search['adults']+$search['children']):$available;
   $property->setAttribute('available_rooms_count',$eligible->count());
   $property->setAttribute('available_min_rate',$eligible->min(fn($room)=>$room->type->nightly_rate));
   return true;
  })->values();
  return view('public.marketplace',compact('properties','search','nights'));
 }
 public function hotel(Request $request,string $slug,BookingService $booking){
  $property=Property::where('slug',$slug)->where('published',true)->firstOrFail();
  $dates=$request->validate(['check_in'=>'nullable|date_format:Y-m-d|after_or_equal:today','check_out'=>'nullable|date_format:Y-m-d|after:check_in','guests'=>'nullable|integer|min:1|max:12']);
  $arrival=$dates['check_in']??today()->toDateString();$departure=$dates['check_out']??\Carbon\Carbon::parse($arrival)->addDay()->toDateString();$guests=(int)($dates['guests']??2);
  try{$rooms=$booking->available($property,$arrival,$departure,$guests)->get()->groupBy('room_type_id');}catch(\InvalidArgumentException $e){throw \Illuminate\Validation\ValidationException::withMessages(['check_out'=>$e->getMessage()]);}
  $types=$property->roomTypes()->get();return view('public.hotel',compact('property','types','rooms','arrival','departure','guests'));
 }
 public function book(Request $request,string $slug,BookingService $booking,HotelController $validator){$property=Property::where('slug',$slug)->where('published',true)->firstOrFail();$data=$validator->validateBooking($request);$reservation=$booking->create($property,$data);return redirect('/booking/'.$reservation->access_token);}
 public function confirmation(string $token){$reservation=Reservation::where('access_token',$token)->with(['room.type','property','payments'])->firstOrFail();return response()->view('public.confirmation',compact('reservation'))->header('Cache-Control','private, no-store')->header('Referrer-Policy','no-referrer')->header('X-Robots-Tag','noindex, nofollow');}
 public function calendar(string $token){
  $room=Room::where('calendar_token',$token)->firstOrFail();$reservations=$room->reservations()->whereIn('status',['confirmed','checked_in'])->whereDate('check_out','>',today())->get();
  $lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Stay OS//Availability//EN','CALSCALE:GREGORIAN','METHOD:PUBLISH'];
  foreach($reservations as $r){$lines=array_merge($lines,['BEGIN:VEVENT','UID:stay-'.$r->id.'-room-'.$room->id.'@stayos','DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),'DTSTART;VALUE=DATE:'.$r->check_in->format('Ymd'),'DTEND;VALUE=DATE:'.$r->check_out->format('Ymd'),'SUMMARY:Unavailable','END:VEVENT']);}
  $lines[]='END:VCALENDAR';return response(implode("\r\n",$lines)."\r\n",200,['Content-Type'=>'text/calendar; charset=utf-8','Cache-Control'=>'private, no-store']);
 }
}
