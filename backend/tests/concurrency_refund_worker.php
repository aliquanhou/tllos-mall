<?php
/**
 * P1-REFUND-FOUNDATION-R1: Real concurrent refund test worker.
 *
 * Usage: php concurrency_refund_worker.php <order_id> <user_id> <amount> <output_file>
 *
 * This runs as an independent PHP process to test true concurrency:
 * two workers simultaneously attempt to create refunds on the same order.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Modules\Refund\Services\RefundService;
use Illuminate\Support\Facades\DB;

$orderId = (int)$argv[1];
$userId = (int)$argv[2];
$amount = (float)$argv[3];
$outputFile = $argv[4];

$startTime = microtime(true);

try {
    $service = app(RefundService::class);
    $result = $service->createRefund([
        'order_id' => $orderId,
        'user_id' => $userId,
        'refund_amount' => $amount,
        'reason' => "concurrent test {$amount}",
        'type' => 1,
    ]);
    $duration = round((microtime(true) - $startTime) * 1000, 2);

    $output = [
        'success' => $result['success'],
        'refund_id' => $result['refund_id'] ?? null,
        'refund_no' => $result['refund_no'] ?? null,
        'message' => $result['message'] ?? null,
        'duration_ms' => $duration,
        'pid' => getmypid(),
    ];
} catch (\Throwable $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $output = [
        'success' => false,
        'error' => $e->getMessage(),
        'duration_ms' => $duration,
        'pid' => getmypid(),
    ];
}

file_put_contents($outputFile, json_encode($output, JSON_PRETTY_PRINT));
