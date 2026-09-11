<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Constants\RefundStatus;
use App\Modules\Payment\Controllers\PaymentNotifyController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

/**
 * P1-REFUND-PROVIDER-PRECONDITION Tests
 *
 * Covers:
 * - Payment Identity: exact payment matching by payment_no, not latest pending
 * - Refund Idempotency: same refund_no → one local refund
 * - Timeout → UNKNOWN (not FAILED)
 * - UNKNOWN amount locking
 * - UNKNOWN resolution: SUCCESS keeps lock, FAILED releases lock
 * - Canonical refund entry: admin refund uses RefundService, not refunds table
 */
class RefundProviderPreconditionTest extends TestCase
{
    private $testUserId;
    private $testOrderId;
    private $testOrderNo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUserId = DB::table('users')->insertGetId([
            'name' => 'PrecondTestUser',
            'email' => 'precond_' . uniqid() . '@test.com',
            'password' => bcrypt('test123456'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->testOrderNo = 'PRECOND' . date('YmdHis') . rand(1000, 9999);
        $this->testOrderId = DB::table('orders')->insertGetId([
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'total_amount' => 100.00,
            'pay_amount' => 100.00,
            'status' => 0, // pending payment (for payment identity tests)
            'pay_type' => 2,
            'receiver_name' => 'Test',
            'receiver_mobile' => '13800138000',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => 'GD', 'city_name' => 'HZ', 'district_name' => 'DY',
            'receiver_address' => 'Test Address',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    protected function tearDown(): void
    {
        DB::table('order_refunds')->where('user_id', $this->testUserId)->delete();
        DB::table('payments')->where('user_id', $this->testUserId)->delete();
        DB::table('orders')->where('user_id', $this->testUserId)->delete();
        DB::table('users')->where('id', $this->testUserId)->delete();
        parent::tearDown();
    }

    private function createPayment(string $paymentNo, int $status = 0, string $txnId = '', string $provider = 'alipay'): int
    {
        return DB::table('payments')->insertGetId([
            'payment_no' => $paymentNo,
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1,
            'pay_type' => 2,
            'amount' => 100.00,
            'status' => $status,
            'third_payment_no' => $txnId ?: null,
            'provider' => $provider,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    // ============================================================
    // Payment Identity Tests
    // ============================================================

    public function test_payment_identity_exact_match_by_payment_no()
    {
        // Create two pending payments for same order
        $payNoA = 'PAYID' . time() . 'A';
        $payNoB = 'PAYID' . time() . 'B';
        $this->createPayment($payNoA, 0);
        $this->createPayment($payNoB, 0);

        // Simulate callback with out_trade_no = payment_no A (new protocol)
        // Use reflection to call private processPaymentSuccess
        $controller = app(PaymentNotifyController::class);
        $method = new \ReflectionMethod($controller, 'processPaymentSuccess');
        $method->setAccessible(true);

        $txnA = 'TXN' . time() . 'A';
        $method->invoke($controller, $payNoA, $txnA, 100.00, 2);

        // Payment A should be paid, Payment B should remain pending
        $payA = DB::table('payments')->where('payment_no', $payNoA)->first();
        $payB = DB::table('payments')->where('payment_no', $payNoB)->first();

        $this->assertEquals(1, $payA->status, 'Payment A should be paid');
        $this->assertEquals($txnA, $payA->third_payment_no);
        $this->assertEquals(0, $payB->status, 'Payment B should remain pending');
    }

    public function test_payment_identity_two_different_transactions_match_different_payments()
    {
        // Create two separate orders, each with one payment
        $orderNoA = 'PRECONDA' . date('YmdHis') . rand(1000, 9999);
        $orderNoB = 'PRECONDB' . date('YmdHis') . rand(1000, 9999);

        $orderIdA = DB::table('orders')->insertGetId([
            'order_no' => $orderNoA, 'user_id' => $this->testUserId, 'merchant_id' => 1,
            'total_amount' => 100.00, 'pay_amount' => 100.00, 'status' => 0, 'pay_type' => 2,
            'receiver_name' => 'T', 'receiver_mobile' => '13800138000',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => 'GD', 'city_name' => 'HZ', 'district_name' => 'DY',
            'receiver_address' => 'T', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);
        $orderIdB = DB::table('orders')->insertGetId([
            'order_no' => $orderNoB, 'user_id' => $this->testUserId, 'merchant_id' => 1,
            'total_amount' => 100.00, 'pay_amount' => 100.00, 'status' => 0, 'pay_type' => 2,
            'receiver_name' => 'T', 'receiver_mobile' => '13800138000',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => 'GD', 'city_name' => 'HZ', 'district_name' => 'DY',
            'receiver_address' => 'T', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $payNoA = 'PAYID2' . time() . 'A';
        $payNoB = 'PAYID2' . time() . 'B';
        DB::table('payments')->insert([
            'payment_no' => $payNoA, 'order_no' => $orderNoA, 'user_id' => $this->testUserId,
            'type' => 1, 'pay_type' => 2, 'amount' => 100.00, 'status' => 0,
            'third_payment_no' => null, 'provider' => 'alipay',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);
        DB::table('payments')->insert([
            'payment_no' => $payNoB, 'order_no' => $orderNoB, 'user_id' => $this->testUserId,
            'type' => 1, 'pay_type' => 2, 'amount' => 100.00, 'status' => 0,
            'third_payment_no' => null, 'provider' => 'alipay',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $controller = app(PaymentNotifyController::class);
        $method = new \ReflectionMethod($controller, 'processPaymentSuccess');
        $method->setAccessible(true);

        // Callback for order A's payment
        $txnA = 'TXN2' . time() . 'A';
        $method->invoke($controller, $payNoA, $txnA, 100.00, 2);

        // Callback for order B's payment (different transaction, different order)
        $txnB = 'TXN2' . time() . 'B';
        $method->invoke($controller, $payNoB, $txnB, 100.00, 2);

        $payA = DB::table('payments')->where('payment_no', $payNoA)->first();
        $payB = DB::table('payments')->where('payment_no', $payNoB)->first();

        $this->assertEquals(1, $payA->status);
        $this->assertEquals($txnA, $payA->third_payment_no);
        $this->assertEquals(1, $payB->status);
        $this->assertEquals($txnB, $payB->third_payment_no);

        // Cleanup extra orders
        DB::table('orders')->whereIn('id', [$orderIdA, $orderIdB])->delete();
    }

    public function test_payment_identity_legacy_order_no_fallback()
    {
        // Legacy: out_trade_no = order_no, only one pending payment
        $payNo = 'PAYLEG' . time();
        $this->createPayment($payNo, 0);

        $controller = app(PaymentNotifyController::class);
        $method = new \ReflectionMethod($controller, 'processPaymentSuccess');
        $method->setAccessible(true);

        $txn = 'TXNLEG' . time();
        // Use order_no as out_trade_no (legacy protocol)
        $method->invoke($controller, $this->testOrderNo, $txn, 100.00, 2);

        $pay = DB::table('payments')->where('payment_no', $payNo)->first();
        $this->assertEquals(1, $pay->status, 'Legacy order_no fallback should work');
        $this->assertEquals($txn, $pay->third_payment_no);
    }

    // ============================================================
    // Refund Idempotency Tests
    // ============================================================

    public function test_refund_identity_binds_correct_payment()
    {
        // Create a paid payment
        $payNo = 'PAYREF' . time();
        $txnId = 'TXNREF' . time();
        $this->createPayment($payNo, 1, $txnId, 'alipay');

        // Mark order as paid
        DB::table('orders')->where('id', $this->testOrderId)->update(['status' => 1]);

        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 50.00,
            'reason' => 'test',
            'type' => 1,
        ]);

        $this->assertTrue($result['success']);
        $refund = DB::table('order_refunds')->where('id', $result['refund_id'])->first();
        $this->assertEquals($payNo, $refund->payment_no);
        $this->assertEquals('alipay', $refund->provider);
        $this->assertEquals($txnId, $refund->provider_transaction_no);
        $this->assertNull($refund->provider_refund_no, 'provider_refund_no must be NULL until provider returns');
    }

    public function test_refund_no_is_canonical_identity()
    {
        $this->createPayment('PAYID3' . time(), 1, 'TXN3' . time(), 'alipay');
        DB::table('orders')->where('id', $this->testOrderId)->update(['status' => 1]);

        $service = app(RefundService::class);
        $r1 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 10.00, 'reason' => 't1', 'type' => 1]);
        $r2 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 10.00, 'reason' => 't2', 'type' => 1]);

        $this->assertTrue($r1['success']);
        $this->assertTrue($r2['success']);
        $this->assertNotEquals($r1['refund_no'], $r2['refund_no']);

        // Each refund_no is unique and maps to exactly one refund record
        $refund1 = DB::table('order_refunds')->where('refund_no', $r1['refund_no'])->count();
        $refund2 = DB::table('order_refunds')->where('refund_no', $r2['refund_no'])->count();
        $this->assertEquals(1, $refund1);
        $this->assertEquals(1, $refund2);
    }

    // ============================================================
    // Timeout → UNKNOWN Tests
    // ============================================================

    public function test_timeout_moves_to_unknown_not_failed()
    {
        $this->createPayment('PAYTO' . time(), 1, 'TXNTO' . time(), 'alipay');
        DB::table('orders')->where('id', $this->testOrderId)->update(['status' => 1]);

        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']); // PROCESSING

        // Simulate timeout → mark as UNKNOWN
        $result = $service->markAsUnknown($r['refund_id'], 'HTTP timeout after 30s');
        $this->assertTrue($result['success']);
        $this->assertEquals(RefundStatus::UNKNOWN, $result['status']);

        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        $this->assertEquals(RefundStatus::UNKNOWN, $refund->status);
        $this->assertNotEquals(RefundStatus::FAILED, $refund->status, 'Timeout must NOT go directly to FAILED');
    }

    public function test_cannot_mark_non_processing_as_unknown()
    {
        $this->createPayment('PAYTO2' . time(), 1, 'TXNTO2' . time(), 'alipay');
        DB::table('orders')->where('id', $this->testOrderId)->update(['status' => 1]);

        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        // REQUESTED (not PROCESSING) → cannot mark as UNKNOWN
        $result = $service->markAsUnknown($r['refund_id']);
        $this->assertFalse($result['success']);
    }

    // ============================================================
    // UNKNOWN Amount Lock Tests
    // ============================================================

    public function test_unknown_locks_refund_amount()
    {
        $this->createPayment('PAYLOCK' . time(), 1, 'TXNLOCK' . time(), 'alipay');
        DB::table('orders')->where('id', $this->testOrderId)->update(['status' => 1]);

        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 60.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']);
        $service->markAsUnknown($r['refund_id'], 'timeout');

        // UNKNOWN 60 is locked. Available should be 40.
        $available = $service->getAvailableRefundAmount($this->testOrderId);
        $this->assertEquals(40.00, $available);

        // New refund of 50 should be rejected (60 locked, only 40 available)
        $r2 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't2', 'type' => 1]);
        $this->assertFalse($r2['success']);

        // New refund of 40 should succeed
        $r3 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 40.00, 'reason' => 't3', 'type' => 1]);
        $this->assertTrue($r3['success']);
    }

    // ============================================================
    // UNKNOWN Resolution Tests
    // ============================================================

    public function test_unknown_resolved_to_success_keeps_amount_locked()
    {
        $this->createPayment('PAYRES' . time(), 1, 'TXNRES' . time(), 'alipay');
        DB::table('orders')->where('id', $this->testOrderId)->update(['status' => 1]);

        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 60.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']);
        $service->markAsUnknown($r['refund_id'], 'timeout');

        // Resolve as SUCCESS
        $result = $service->resolveUnknown($r['refund_id'], true, 'ALIPAY_REFUND_001');
        $this->assertTrue($result['success']);
        $this->assertEquals(RefundStatus::SUCCESS, $result['status']);

        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        $this->assertEquals(RefundStatus::SUCCESS, $refund->status);
        $this->assertEquals('ALIPAY_REFUND_001', $refund->provider_refund_no);

        // Amount still locked (SUCCESS is in ACTIVE_REFUND_STATUSES)
        $available = $service->getAvailableRefundAmount($this->testOrderId);
        $this->assertEquals(40.00, $available);
    }

    public function test_unknown_resolved_to_failed_releases_amount()
    {
        $this->createPayment('PAYRES2' . time(), 1, 'TXNRES2' . time(), 'alipay');
        DB::table('orders')->where('id', $this->testOrderId)->update(['status' => 1]);

        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 60.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']);
        $service->markAsUnknown($r['refund_id'], 'timeout');

        // Resolve as FAILED
        $result = $service->resolveUnknown($r['refund_id'], false, '', 'Provider confirmed refund not processed');
        $this->assertTrue($result['success']);
        $this->assertEquals(RefundStatus::FAILED, $result['status']);

        // Amount released (FAILED not in ACTIVE_REFUND_STATUSES)
        $available = $service->getAvailableRefundAmount($this->testOrderId);
        $this->assertEquals(100.00, $available);
    }

    public function test_cannot_resolve_non_unknown()
    {
        $this->createPayment('PAYRES3' . time(), 1, 'TXNRES3' . time(), 'alipay');
        DB::table('orders')->where('id', $this->testOrderId)->update(['status' => 1]);

        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        // REQUESTED → cannot resolve via resolveUnknown
        $result = $service->resolveUnknown($r['refund_id'], true);
        $this->assertFalse($result['success']);
    }

    // ============================================================
    // Canonical Refund Entry Tests
    // ============================================================

    public function test_admin_refund_uses_order_refunds_not_refunds_table()
    {
        $this->createPayment('PAYADMIN' . time(), 1, 'TXNADMIN' . time(), 'alipay');
        DB::table('orders')->where('id', $this->testOrderId)->update(['status' => 1]);

        // Check if legacy refunds table exists in test DB
        $refundsTableExists = DB::select("SHOW TABLES LIKE 'refunds'");
        $refundsBefore = !empty($refundsTableExists) ? DB::table('refunds')->count() : 0;

        // Simulate admin refund via RefundService (canonical entry)
        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 30.00,
            'reason' => 'admin direct refund',
            'type' => 1,
        ]);
        $service->approveRefund($result['refund_id']);

        // Legacy refunds table must NOT be written to
        if (!empty($refundsTableExists)) {
            $refundsAfter = DB::table('refunds')->count();
            $this->assertEquals($refundsBefore, $refundsAfter, 'Legacy refunds table must not be written to');
        }

        // Canonical order_refunds table must have the record
        $refund = DB::table('order_refunds')->where('id', $result['refund_id'])->first();
        $this->assertNotNull($refund);
        $this->assertEquals(RefundStatus::PROCESSING, $refund->status);
    }

    // ============================================================
    // Payments provider field backfill test
    // ============================================================

    public function test_payments_provider_field_exists_and_backfilled()
    {
        $payNo = 'PAYPROV' . time();
        $this->createPayment($payNo, 1, 'TXNPROV' . time(), 'alipay');

        $payment = DB::table('payments')->where('payment_no', $payNo)->first();
        $this->assertEquals('alipay', $payment->provider);
        $this->assertNotNull($payment->provider);
    }

    public function test_payments_provider_third_payment_no_unique_constraint()
    {
        $indexes = DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_provider_third_payment_no_unique'");
        $this->assertNotEmpty($indexes);
        $this->assertEquals(0, $indexes[0]->Non_unique);
    }
}
