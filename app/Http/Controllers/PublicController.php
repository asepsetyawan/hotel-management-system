<?php
namespace App\Http\Controllers;
use App\Models\{Property,Reservation,Room};
use App\Services\BookingService;
use App\Domain\Stay;
use Illuminate\Http\Request;
class PublicController {
 public function marketplace(Request $request){$q=Property::where('published',true)->withMin('roomTypes','nightly_rate')->withCount('rooms');if($request->filled('city'))$q->where('city','like','%'.substr($request->string('city')->toString(),0,80).'%');$properties=$q->get();return view('public.marketplace',compact('properties'));}
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
