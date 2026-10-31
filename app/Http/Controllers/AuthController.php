<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
class AuthController {
 public function form(){return auth()->check()?redirect('/app'):view('public.login');}
 public function login(Request $request){$credentials=$request->validate(['email'=>'required|email','password'=>'required|string']);if(!Auth::attempt($credentials))throw ValidationException::withMessages(['email'=>'These credentials do not match our records.']);$request->session()->regenerate();return redirect()->intended('/app');}
 public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect('/login');}
}
