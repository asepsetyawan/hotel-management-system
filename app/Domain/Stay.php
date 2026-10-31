<?php
namespace App\Domain;
use DateTimeImmutable;
use InvalidArgumentException;
final class Stay {
 /** Checkout is exclusive: the next guest can arrive on the same date. */
 public static function nights(string $arrival,string $departure):array {
  $start=DateTimeImmutable::createFromFormat('!Y-m-d',$arrival);$end=DateTimeImmutable::createFromFormat('!Y-m-d',$departure);
  if(!$start||!$end||$start->format('Y-m-d')!==$arrival||$end->format('Y-m-d')!==$departure||$end<=$start||$start->diff($end)->days>90) throw new InvalidArgumentException('Choose a valid stay between 1 and 90 nights.');
  $nights=[]; for($day=$start;$day<$end;$day=$day->modify('+1 day')) $nights[]=$day->format('Y-m-d');return $nights;
 }
 public static function quote(int $rate,int $nights,int $taxBasisPoints):array {
  if($rate<0||$nights<1||$nights>90||$taxBasisPoints<0||$taxBasisPoints>10000) throw new InvalidArgumentException('Invalid rate, duration or tax.');
  $subtotal=$rate*$nights;$tax=intdiv($subtotal*$taxBasisPoints+5000,10000);return compact('subtotal','tax')+['total'=>$subtotal+$tax];
 }
 public static function canTransition(string $from,string $to):bool {return in_array($to,match($from){'confirmed'=>['checked_in','cancelled'],'checked_in'=>['checked_out'],default=>[]},true);}
}
