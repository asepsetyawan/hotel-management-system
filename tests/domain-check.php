<?php
// Dependency-free checks. Run with: php tests/domain-check.php
require __DIR__.'/../app/Domain/Stay.php';
use App\Domain\Stay;
$checks=0;
function check(bool $ok,string $name):void {global $checks;if(!$ok)throw new RuntimeException('FAIL: '.$name);$checks++;echo "PASS: $name\n";}
function rejects(callable $fn,string $name):void {try{$fn();}catch(InvalidArgumentException){check(true,$name);return;}check(false,$name);}
check(Stay::nights('2026-10-30','2026-11-01')===['2026-10-30','2026-10-31'],'checkout is exclusive across month boundary');
check(count(Stay::nights('2028-02-28','2028-03-01'))===2,'leap day included');
check(count(Stay::nights('2026-12-31','2027-01-01'))===1,'year boundary');
check(count(Stay::nights('2026-01-01','2026-04-01'))===90,'maximum stay accepted');
rejects(fn()=>Stay::nights('2026-01-01','2026-04-02'),'91 nights rejected');
rejects(fn()=>Stay::nights('2026-02-30','2026-03-02'),'invalid calendar date rejected');
rejects(fn()=>Stay::nights('2026-10-30','2026-10-30'),'zero night stay rejected');
rejects(fn()=>Stay::nights('2026-10-31','2026-10-30'),'reversed dates rejected');
rejects(fn()=>Stay::nights('yesterday','tomorrow'),'unformatted dates rejected');
check(Stay::quote(850000,3,1100)===['subtotal'=>2550000,'tax'=>280500,'total'=>2830500],'IDR tax quote uses integer arithmetic');
check(Stay::quote(5,1,1100)['tax']===1,'tax rounds half up');
check(Stay::quote(850000,1,0)['total']===850000,'zero tax');
rejects(fn()=>Stay::quote(-1,1,1100),'negative rate rejected');
rejects(fn()=>Stay::quote(850000,0,1100),'zero nights in quote rejected');
rejects(fn()=>Stay::quote(850000,1,10001),'invalid tax rate rejected');
foreach([['confirmed','checked_in',true],['confirmed','cancelled',true],['checked_in','checked_out',true],['checked_out','checked_in',false],['cancelled','checked_in',false],['confirmed','checked_out',false],['checked_in','cancelled',false]] as [$from,$to,$allowed])check(Stay::canTransition($from,$to)===$allowed,"transition $from → $to");
echo "$checks domain checks passed.\n";
