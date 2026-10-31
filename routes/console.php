<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\{User,Property};
Artisan::command('stayos:owner',function(){
 $name=$this->ask('Owner name');$email=$this->ask('Owner email');$password=$this->secret('Password (at least 12 characters)');
 $validator=validator(compact('name','email','password'),['name'=>'required|string|max:100','email'=>'required|email|unique:users,email','password'=>'required|string|min:12|max:128']);
 if($validator->fails()){foreach($validator->errors()->all() as $error)$this->error($error);return 1;}
 DB::transaction(function()use($name,$email,$password){$user=User::create(compact('name','email','password')+['role'=>'owner']);$property=Property::firstOrCreate(['slug'=>'my-hotel'],['name'=>'My Hotel','city'=>'Jakarta','address'=>'Update your address','description'=>'Welcome to our hotel.','tagline'=>'Your next memorable stay.','email'=>$email,'phone'=>'Update your phone','published'=>false]);$user->properties()->attach($property);});
 $this->info('Owner created. Sign in, update property settings, and add your rooms.');return 0;
})->purpose('Create an owner and assign an initial draft property without demo data');
