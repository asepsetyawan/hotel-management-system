<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Reservation extends Model {
 protected $guarded=['id']; protected $hidden=['access_token'];
 protected function casts():array{return ['check_in'=>'date','check_out'=>'date','checked_in_at'=>'datetime','checked_out_at'=>'datetime'];}
 public function property(){return $this->belongsTo(Property::class);} public function room(){return $this->belongsTo(Room::class);} public function payments(){return $this->hasMany(Payment::class);}
 public function getPaidAttribute():int {return (int)($this->payments_sum_amount ?? $this->payments->sum('amount'));}
 public function getBalanceAttribute():int {return max(0,$this->total-$this->paid);}
}
