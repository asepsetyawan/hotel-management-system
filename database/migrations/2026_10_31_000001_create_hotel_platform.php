<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('users',function(Blueprint $t){$t->id();$t->string('name');$t->string('email')->unique();$t->string('password');$t->string('role')->default('frontdesk');$t->rememberToken();$t->timestamps();});
  Schema::create('properties',function(Blueprint $t){$t->id();$t->string('name');$t->string('slug')->unique();$t->string('city');$t->string('address');$t->text('description');$t->string('tagline');$t->string('email');$t->string('phone');$t->string('currency',3)->default('IDR');$t->unsignedInteger('tax_basis_points')->default(1100);$t->boolean('published')->default(false);$t->timestamps();});
  Schema::create('property_user',function(Blueprint $t){$t->foreignId('property_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->primary(['property_id','user_id']);});
  Schema::create('room_types',function(Blueprint $t){$t->id();$t->foreignId('property_id')->constrained();$t->string('name');$t->text('description');$t->unsignedInteger('capacity');$t->unsignedBigInteger('nightly_rate');$t->string('amenities');$t->timestamps();});
  Schema::create('rooms',function(Blueprint $t){$t->id();$t->foreignId('property_id')->constrained();$t->foreignId('room_type_id')->constrained();$t->string('number');$t->string('floor');$t->string('housekeeping_status')->default('clean');$t->boolean('out_of_service')->default(false);$t->string('calendar_token',64)->unique();$t->timestamps();$t->unique(['property_id','number']);});
  Schema::create('reservations',function(Blueprint $t){$t->id();$t->foreignId('property_id')->constrained();$t->foreignId('room_id')->constrained();$t->string('reference')->unique();$t->uuid('access_token')->unique();$t->string('guest_name');$t->string('guest_email');$t->string('guest_phone')->nullable();$t->unsignedInteger('guests');$t->date('check_in');$t->date('check_out');$t->string('status')->default('confirmed');$t->string('source')->default('direct');$t->unsignedBigInteger('nightly_rate');$t->unsignedBigInteger('subtotal');$t->unsignedBigInteger('tax');$t->unsignedBigInteger('total');$t->text('notes')->nullable();$t->timestamp('checked_in_at')->nullable();$t->timestamp('checked_out_at')->nullable();$t->timestamps();$t->index(['property_id','check_in','check_out','status']);});
  Schema::create('room_nights',function(Blueprint $t){$t->id();$t->foreignId('room_id')->constrained();$t->foreignId('reservation_id')->constrained();$t->date('night');$t->unique(['room_id','night']);});
  Schema::create('payments',function(Blueprint $t){$t->id();$t->foreignId('reservation_id')->constrained();$t->foreignId('user_id')->constrained();$t->unsignedBigInteger('amount');$t->string('method');$t->uuid('idempotency_key')->unique();$t->string('note')->nullable();$t->timestamps();});
  Schema::create('audit_events',function(Blueprint $t){$t->id();$t->foreignId('property_id')->constrained();$t->foreignId('user_id')->nullable()->constrained();$t->string('action');$t->string('subject');$t->timestamps();});
 }
 public function down(): void {foreach(['audit_events','payments','room_nights','reservations','rooms','room_types','property_user','properties','users'] as $name) Schema::dropIfExists($name);}
};
