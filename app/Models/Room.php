<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Room extends Model {
 protected $guarded=['id']; protected $hidden=['calendar_token']; protected function casts():array{return ['out_of_service'=>'boolean'];}
 public function property(){return $this->belongsTo(Property::class);} public function type(){return $this->belongsTo(RoomType::class,'room_type_id');}
 public function reservations(){return $this->hasMany(Reservation::class);}
 public function nights(){return $this->hasMany(RoomNight::class);}
}
