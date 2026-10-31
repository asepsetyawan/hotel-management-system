<?php
return ['name'=>env('APP_NAME','Stay OS'),'env'=>env('APP_ENV','production'),'debug'=>(bool)env('APP_DEBUG',false),'url'=>env('APP_URL','http://localhost'),'timezone'=>env('APP_TIMEZONE','Asia/Jakarta'),'locale'=>'en','fallback_locale'=>'en','faker_locale'=>'en_US','cipher'=>'AES-256-CBC','key'=>env('APP_KEY'),'previous_keys'=>[],'maintenance'=>['driver'=>'file']];
