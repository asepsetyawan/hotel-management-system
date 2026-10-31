<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class StaffAccess {
 public function handle(Request $request,Closure $next){
  abort_unless(in_array($request->user()->role,['owner','manager','frontdesk','housekeeping'],true),403);
  $response=$next($request);
  $response->headers->set('Cache-Control','private, no-store');
  $response->headers->set('X-Content-Type-Options','nosniff');
  return $response;
 }
}
