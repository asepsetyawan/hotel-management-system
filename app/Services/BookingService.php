<?php
namespace App\Services;
use App\Domain\Stay;
use App\Models\{Property,Room,RoomNight,Reservation,AuditEvent};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class BookingService {
 public function available(Property $property,string $arrival,string $departure,int $guests){
  $nights=Stay::nights($arrival,$departure);
  return $property->rooms()->with('type')->where('out_of_service',false)
   ->whereHas('type',fn($q)=>$q->where('capacity','>=',$guests))
   ->whereDoesntHave('nights',fn($q)=>$q->whereIn('night',$nights));
 }
 public function create(Property $property,array $data,string $source='direct'):Reservation {
  return DB::transaction(function()use($property,$data,$source){
   // Serialize inventory writes for the selected room; the unique nightly index is a second guard.
   $room=$property->rooms()->with('type')->lockForUpdate()->findOrFail($data['room_id']);
   $nights=Stay::nights($data['check_in'],$data['check_out']);
   if($room->out_of_service||$room->type->capacity<$data['guests']||$room->nights()->whereIn('night',$nights)->exists()) throw ValidationException::withMessages(['room_id'=>'This room is no longer available for the selected dates.']);
   $quote=Stay::quote($room->type->nightly_rate,count($nights),$property->tax_basis_points);
   $reservation=Reservation::create(array_merge(collect($data)->only(['guest_name','guest_email','guest_phone','guests','check_in','check_out','notes'])->all(),$quote,[
    'property_id'=>$property->id,'room_id'=>$room->id,'reference'=>'ST-'.strtoupper(Str::random(8)),'access_token'=>(string)Str::uuid(),'status'=>'confirmed','source'=>$source,'nightly_rate'=>$room->type->nightly_rate]));
   foreach($nights as $night) RoomNight::create(['room_id'=>$room->id,'reservation_id'=>$reservation->id,'night'=>$night]);
   $this->audit($property->id,'Reservation created',$reservation->reference);
   return $reservation;
  },5);
 }
 public function transition(Reservation $reservation,string $status):void {
  DB::transaction(function()use($reservation,$status){
   // Keep lock ordering consistent with create(): room first, then reservation.
   $room=Room::lockForUpdate()->findOrFail($reservation->room_id);
   $r=Reservation::lockForUpdate()->with('payments')->findOrFail($reservation->id);
   if(!Stay::canTransition($r->status,$status)) throw ValidationException::withMessages(['status'=>'That reservation status change is not allowed.']);
   if($status==='checked_in'&&($r->check_in->isAfter(today())||$r->check_out->lte(today())||$room->housekeeping_status!=='clean'||$room->out_of_service||$room->reservations()->where('status','checked_in')->whereKeyNot($r->id)->exists())) throw ValidationException::withMessages(['status'=>'Check-in requires a clean, operational room, no current occupant, and a stay covering today.']);
   if($status==='checked_out'&&$r->balance>0) throw ValidationException::withMessages(['status'=>'Record the outstanding balance before checkout.']);
   $r->status=$status;
   if($status==='checked_in')$r->checked_in_at=now();
   if($status==='checked_out'){$r->checked_out_at=now();$room->update(['housekeeping_status'=>'dirty']);}
   if(in_array($status,['cancelled','checked_out'])) RoomNight::where('reservation_id',$r->id)->delete();
   $r->save();$this->audit($r->property_id,'Reservation '.$status,$r->reference);
  },5);
 }
 public function audit(int $propertyId,string $action,string $subject):void {AuditEvent::create(['property_id'=>$propertyId,'user_id'=>auth()->id(),'action'=>$action,'subject'=>$subject]);}
}
