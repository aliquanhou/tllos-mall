<?php
/**
 * P1 Concurrency Test: Two independent PHP processes simultaneously
 * attempt to process payment success for the same order/payment.
 *
 * This verifies that lockForUpdate() truly prevents double payment:
 *   T1: BEGIN → lock payment → update → COMMIT
 *   T2: BEGIN → lock payment (WAIT) → T1 COMMIT → T2 resume → sees paid → NO-OP
 *
 * Usage: php concurrency_worker.php <order_no> <transaction_id> <amount> <pay_type>
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$orderNo = $argv[1] ?? die("Missing order_no\n");
$txnId = $argv[2] ?? die("Missing transaction_id\n");
$amount = $argv[3] ?? die("Missing amount\n");
$payType = $argv[4] ?? 2;
$workerId = $argv[5] ?? 'W';

// Fail-Closed: refuse to run against production
$dbName = config('database.connections.mysql.database');
if ($dbName === 'tllos_mall') {
    fwrite(STDERR, "FATAL: Refusing concurrency test against production database\n");
    exit(1);
}

echo "[$workerId] Starting: order=$orderNo txn=$txnId amount=$amount\n";
echo "[$workerId] DB=" . config('database.connections.mysql.database') . "\n";

$startTime = microtime(true);

$controller = new \App\Modules\Payment\Controllers\PaymentNotifyController();
$method = new \ReflectionMethod($controller, 'processPaymentSuccess');
$method->setAccessible(true);
$method->invoke($controller, $orderNo, $txnId, $amount, $payType);

$elapsed = round(microtime(true) - $startTime, 3);
echo "[$workerId] Completed in ${elapsed}s\n";

// Verify final state
$payment = DB::table('payments')->where('order_no', $orderNo)->first();
$order = DB::table('orders')->where('order_no', $orderNo)->first();
echo "[$workerId] Final: order_status={$order->status} payment_status={$payment->status} txn={$payment->third_payment_no}\n";
