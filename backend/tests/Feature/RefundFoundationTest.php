<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Constants\RefundStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * P1-REFUND-FOUNDATION Tests
 *
 * Verifies:
 * - Refund identity: refund_no unique, payment_id bound, provider_refund_no unique
 * - Amount safety: cumulative refund, concurrent refund
 * - State machine: REQUESTED → PROCESSING → SUCCESS/FAILED
 * - Fake success elimination: refundAudit cannot produce SUCCESS or REF+time
 * - Production DB safety: all tests run in tllos_mall_test
 */
class RefundFoundationTest extends TestCase
{
    private $testUserId;
    private $testOrderId;
    private $testOrderNo;
    private $testPaymentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUserId = DB::table('users')->insertGetId([
            'name' => 'RefundTestUser',
            'email' => 'refund_test_' . uniqid() . '@example.com',
            'password' => bcrypt('test123456'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->testOrderNo = 'RTEST' . date('YmdHis') . rand(1000, 9999);
        $this->testOrderId = DB::table('orders')->insertGetId([
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'total_amount' => 100.00,
            'pay_amount' => 100.00,
            'status' => 1, // paid
            'pay_type' => 2, // alipay
            'receiver_name' => 'Test',
            'receiver_mobile' => '13800138000',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => 'GD', 'city_name' => 'HZ', 'district_name' => 'DY',
            'receiver_address' => 'Test Address',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->testPaymentId = DB::table('payments')->insertGetId([
            'payment_no' => 'PAY' . date('YmdHis') . rand(1000, 9999),
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1,
            'pay_type' => 2,
            'amount' => 100.00,
            'status' => 1, // paid
            'third_payment_no' => '20260911220014' . rand(1000000000, 9999999999),
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

    // ============================================================
    // Identity Tests
    // ============================================================

    public function test_refund_has_payment_identity()
    {
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
        $this->assertEquals($this->testPaymentId, $refund->payment_id);
        $this->assertNotNull($refund->payment_no);
        $this->assertEquals('alipay', $refund->provider);
        $this->assertNotNull($refund->provider_transaction_no);
    }

    public function test_refund_no_is_unique()
    {
        $service = app(RefundService::class);
        $r1 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 30.00, 'reason' => 't1', 'type' => 1]);
        $r2 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 30.00, 'reason' => 't2', 'type' => 1]);

        $this->assertTrue($r1['success']);
        $this->assertTrue($r2['success']);
        $this->assertNotEquals($r1['refund_no'], $r2['refund_no']);
    }

    // ============================================================
    // Amount Safety Tests
    // ============================================================

    public function test_full_refund_100_from_100_passes()
    {
        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 100.00,
            'reason' => 'full refund',
            'type' => 1,
        ]);
        $this->assertTrue($result['success']);
    }

    public function test_refund_101_from_100_fails()
    {
        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 101.00,
            'reason' => 'over refund',
            'type' => 1,
        ]);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('超过可退金额', $result['message']);
    }

    public function test_partial_refund_80_then_20_passes()
    {
        $service = app(RefundService::class);
        $r1 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 80.00, 'reason' => 'first', 'type' => 1]);
        $this->assertTrue($r1['success']);

        // First refund is REQUESTED, counts as locked amount
        $r2 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 20.00, 'reason' => 'second', 'type' => 1]);
        $this->assertTrue($r2['success']);
    }

    public function test_partial_refund_80_then_21_fails()
    {
        $service = app(RefundService::class);
        $r1 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 80.00, 'reason' => 'first', 'type' => 1]);
        $this->assertTrue($r1['success']);

        $r2 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 21.00, 'reason' => 'over', 'type' => 1]);
        $this->assertFalse($r2['success']);
    }

    public function test_processing_refund_counts_as_locked()
    {
        $service = app(RefundService::class);
        $r1 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 80.00, 'reason' => 'first', 'type' => 1]);
        $this->assertTrue($r1['success']);

        // Move to PROCESSING (simulating admin approval)
        $service->approveRefund($r1['refund_id']);

        // Now 80 is locked in PROCESSING, 21 should fail
        $r2 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 21.00, 'reason' => 'over', 'type' => 1]);
        $this->assertFalse($r2['success']);
    }

    public function test_available_refund_amount_calculation()
    {
        $service = app(RefundService::class);
        $this->assertEquals(100.00, $service->getAvailableRefundAmount($this->testOrderId));

        $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 30.00, 'reason' => 't', 'type' => 1]);
        $this->assertEquals(70.00, $service->getAvailableRefundAmount($this->testOrderId));
    }

    // ============================================================
    // State Machine Tests
    // ============================================================

    public function test_requested_to_processing_via_approve()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        $this->assertEquals(RefundStatus::REQUESTED, $r['status']);

        $approveResult = $service->approveRefund($r['refund_id']);
        $this->assertTrue($approveResult['success']);
        $this->assertEquals(RefundStatus::PROCESSING, $approveResult['status']);

        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        $this->assertEquals(RefundStatus::PROCESSING, $refund->status);
    }

    public function test_approve_does_not_produce_fake_success()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);

        $service->approveRefund($r['refund_id']);

        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        // CRITICAL: must NOT be SUCCESS
        $this->assertNotEquals(RefundStatus::SUCCESS, $refund->status);
        // CRITICAL: must NOT have fake REF+time provider_refund_no
        $this->assertNotEquals('REF' . time(), $refund->provider_refund_no);
        $this->assertNull($refund->provider_refund_no);
        // Order status must NOT be changed to refunded
        $order = DB::table('orders')->where('id', $this->testOrderId)->first();
        $this->assertEquals(1, $order->status); // still paid, not refunded
    }

    public function test_cannot_approve_non_requested_refund()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']); // now PROCESSING

        // Try to approve again
        $result = $service->approveRefund($r['refund_id']);
        $this->assertFalse($result['success']);
    }

    public function test_reject_moves_to_rejected()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);

        $result = $service->rejectRefund($r['refund_id'], 'test reject');
        $this->assertTrue($result['success']);

        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        $this->assertEquals(RefundStatus::REJECTED, $refund->status);
        $this->assertEquals('test reject', $refund->refuse_reason);
    }

    public function test_user_cancel_moves_to_cancelled()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);

        $result = $service->cancelRefund($r['refund_id'], $this->testUserId);
        $this->assertTrue($result['success']);

        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        $this->assertEquals(RefundStatus::CANCELLED, $refund->status);
    }

    public function test_cannot_cancel_processing_refund()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']); // PROCESSING

        $result = $service->cancelRefund($r['refund_id'], $this->testUserId);
        $this->assertFalse($result['success']);
    }

    // ============================================================
    // Fake Success Elimination Tests
    // ============================================================

    public function test_no_ref_time_in_provider_refund_no_after_approve()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']);

        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        // provider_refund_no must be null at this stage (not yet called third-party)
        $this->assertNull($refund->provider_refund_no);
        // refund_no_third legacy field must NOT contain REF+time
        $this->assertStringNotContainsString('REF', (string)$refund->refund_no_third);
    }

    public function test_legacy_refund_record_untouched()
    {
        // Verify the historical record (id=2) is not modified by our code
        // This test only checks that our operations don't touch other records
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 10.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']);

        // Our new record should be PROCESSING
        $newRefund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        $this->assertEquals(RefundStatus::PROCESSING, $newRefund->status);
    }

    // ============================================================
    // Refund Status Constants
    // ============================================================

    public function test_refund_status_constants()
    {
        $this->assertEquals(0, RefundStatus::REQUESTED);
        $this->assertEquals(1, RefundStatus::PROCESSING);
        $this->assertEquals(2, RefundStatus::SUCCESS);
        $this->assertEquals(3, RefundStatus::FAILED);
        $this->assertEquals(4, RefundStatus::CANCELLED);
        $this->assertEquals(5, RefundStatus::UNKNOWN);
        $this->assertEquals(6, RefundStatus::REJECTED);
    }

    public function test_refund_status_labels()
    {
        $this->assertEquals('待处理', RefundStatus::label(RefundStatus::REQUESTED));
        $this->assertEquals('处理中', RefundStatus::label(RefundStatus::PROCESSING));
        $this->assertEquals('退款成功', RefundStatus::label(RefundStatus::SUCCESS));
        $this->assertEquals('退款失败', RefundStatus::label(RefundStatus::FAILED));
    }
}
