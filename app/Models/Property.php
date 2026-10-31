<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Property extends Model {
 protected $attributes=['currency'=>'IDR','tax_basis_points'=>1100,'published'=>false];
 protected $guarded=['id']; protected function casts():array{return ['published'=>'boolean','tax_basis_points'=>'integer'];}
 public function rooms(){return $this->hasMany(Room::class);}
 public function roomTypes(){return $this->hasMany(RoomType::class);}
 public function reservations(){return $this->hasMany(Reservation::class);}
}
