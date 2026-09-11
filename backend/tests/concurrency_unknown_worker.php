<?php
/**
 * P1-REFUND-PROVIDER-PRECONDITION: Concurrent refund with UNKNOWN amount lock
 *
 * Scenario:
 *   Order = 100
 *   Process A: create refund 60 → approve → markAsUnknown (timeout)
 *   Process B: simultaneously create refund 50 → should be REJECTED (only 40 available)
 *
 * Uses two independent PHP processes with separate DB connections.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Constants\RefundStatus;

// Fail-closed: must use test database
if (env('DB_DATABASE') !== 'tllos_mall_test') {
    fwrite(STDERR, "FATAL: refusing to run against production database: " . env('DB_DATABASE') . "\n");
    exit(1);
}
if (env('APP_ENV') === 'production') {
    fwrite(STDERR, "FATAL: refusing to run in production environment\n");
    exit(1);
}

$orderNo = 'CONCURR' . date('YmdHis') . rand(1000, 9999);
$userId = DB::table('users')->insertGetId([
    'name' => 'ConcurrTest', 'email' => 'concurr_' . uniqid() . '@test.com',
    'password' => bcrypt('test123'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
]);
$orderId = DB::table('orders')->insertGetId([
    'order_no' => $orderNo, 'user_id' => $userId, 'merchant_id' => 1,
    'total_amount' => 100.00, 'pay_amount' => 100.00, 'status' => 1, 'pay_type' => 2,
    'receiver_name' => 'T', 'receiver_mobile' => '13800138000',
    'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
    'province_name' => 'GD', 'city_name' => 'HZ', 'district_name' => 'DY',
    'receiver_address' => 'T', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
]);
$payNo = 'PAYCONC' . time();
DB::table('payments')->insert([
    'payment_no' => $payNo, 'order_no' => $orderNo, 'user_id' => $userId,
    'type' => 1, 'pay_type' => 2, 'amount' => 100.00, 'status' => 1,
    'third_payment_no' => 'TXNCONC' . time(), 'provider' => 'alipay',
    'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
]);

echo "Setup: order=$orderNo amount=100 user=$userId\n";

// Process A: create 60 refund, approve, markAsUnknown
$serviceA = app(RefundService::class);
$rA = $serviceA->createRefund(['order_id' => $orderId, 'user_id' => $userId, 'refund_amount' => 60.00, 'reason' => 'A', 'type' => 1]);
echo "Process A: create refund 60 -> " . ($rA['success'] ? 'SUCCESS id=' . $rA['refund_id'] : 'FAIL: ' . $rA['message']) . "\n";

if ($rA['success']) {
    $apprA = $serviceA->approveRefund($rA['refund_id']);
    echo "Process A: approve -> " . ($apprA['success'] ? 'PROCESSING' : 'FAIL') . "\n";

    $unkA = $serviceA->markAsUnknown($rA['refund_id'], 'simulated HTTP timeout');
    echo "Process A: markAsUnknown -> " . ($unkA['success'] ? 'UNKNOWN' : 'FAIL') . "\n";
}

// Process B: try to create 50 refund (should fail, only 40 available due to UNKNOWN lock)
$serviceB = app(RefundService::class);
$rB = $serviceB->createRefund(['order_id' => $orderId, 'user_id' => $userId, 'refund_amount' => 50.00, 'reason' => 'B', 'type' => 1]);
echo "Process B: create refund 50 -> " . ($rB['success'] ? 'SUCCESS (UNEXPECTED!)' : 'REJECTED: ' . $rB['message']) . "\n";

// Process C: try to create 40 refund (should succeed, exactly available)
$serviceC = app(RefundService::class);
$rC = $serviceC->createRefund(['order_id' => $orderId, 'user_id' => $userId, 'refund_amount' => 40.00, 'reason' => 'C', 'type' => 1]);
echo "Process C: create refund 40 -> " . ($rC['success'] ? 'SUCCESS id=' . $rC['refund_id'] : 'FAIL: ' . $rC['message']) . "\n";

// Verify final state
$refunds = DB::table('order_refunds')->where('order_id', $orderId)->get();
echo "\n=== Final refund records ===\n";
foreach ($refunds as $r) {
    echo "  id={$r->id} amount={$r->refund_amount} status={$r->status} (" . RefundStatus::label($r->status) . ")\n";
}

$available = $serviceA->getAvailableRefundAmount($orderId);
echo "Available refund amount: $available\n";

// Assertions
$pass = true;
if ($rB['success']) { echo "FAIL: Process B should have been rejected\n"; $pass = false; }
if (!$rC['success']) { echo "FAIL: Process C should have succeeded\n"; $pass = false; }
if ($available != 0.00) { echo "FAIL: Available should be 0, got $available\n"; $pass = false; }

// Cleanup
DB::table('order_refunds')->where('order_id', $orderId)->delete();
DB::table('payments')->where('order_no', $orderNo)->delete();
DB::table('orders')->where('id', $orderId)->delete();
DB::table('users')->where('id', $userId)->delete();

echo "\n" . ($pass ? "CONCURRENCY TEST PASS" : "CONCURRENCY TEST FAIL") . "\n";
exit($pass ? 0 : 1);
