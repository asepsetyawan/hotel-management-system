<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RoomType extends Model {protected $guarded=['id']; public function property(){return $this->belongsTo(Property::class);} public function rooms(){return $this->hasMany(Room::class);}}
