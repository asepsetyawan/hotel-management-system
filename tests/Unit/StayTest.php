<?php
namespace Tests\Unit;
use App\Domain\Stay;
use PHPUnit\Framework\TestCase;
class StayTest extends TestCase {
 public function test_checkout_is_exclusive():void {$this->assertSame(['2026-10-30','2026-10-31'],Stay::nights('2026-10-30','2026-11-01'));}
 public function test_money_uses_integer_rupiah_and_rounds_tax():void {$this->assertSame(['subtotal'=>2550000,'tax'=>280500,'total'=>2830500],Stay::quote(850000,3,1100));$this->assertSame(1,Stay::quote(5,1,1100)['tax']);}
 public function test_invalid_date_is_rejected():void {$this->expectException(\InvalidArgumentException::class);Stay::nights('2026-02-30','2026-03-02');}
 public function test_same_day_stay_is_rejected():void {$this->expectException(\InvalidArgumentException::class);Stay::nights('2026-10-30','2026-10-30');}
 public function test_completed_stays_cannot_reopen():void {$this->assertFalse(Stay::canTransition('checked_out','checked_in'));$this->assertFalse(Stay::canTransition('cancelled','checked_in'));$this->assertTrue(Stay::canTransition('confirmed','cancelled'));}
}
