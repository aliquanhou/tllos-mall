<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Constants\RefundStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * P1-REFUND-FOUNDATION-R1 Tests
 *
 * R1 additions:
 * - Legacy status semantic compatibility (no collision)
 * - refund_no DB UNIQUE constraint
 * - provider + provider_refund_no DB UNIQUE
 * - Double approval safety
 * - PROCESSING presentation (not SUCCESS)
 * - Real concurrent refund amount safety
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
            'status' => 1,
            'pay_type' => 2,
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
            'status' => 1,
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
    // R1: Legacy Status Semantic Compatibility
    // ============================================================

    public function test_legacy_status_values_have_no_semantic_collision()
    {
        // OLD system: 0=pending, 2=rejected, 5=success, 6=cancelled
        // NEW system must preserve these exact meanings
        $this->assertEquals(0, RefundStatus::REQUESTED);  // old=pending → new=requested (same)
        $this->assertEquals(2, RefundStatus::REJECTED);   // old=rejected → new=rejected (same)
        $this->assertEquals(5, RefundStatus::SUCCESS);    // old=success → new=success (same)
        $this->assertEquals(6, RefundStatus::CANCELLED);  // old=cancelled → new=cancelled (same)

        // New states use previously unused numbers
        $this->assertEquals(1, RefundStatus::PROCESSING);
        $this->assertEquals(3, RefundStatus::FAILED);
        $this->assertEquals(4, RefundStatus::UNKNOWN);
    }

    public function test_legacy_status_5_means_success_not_unknown()
    {
        // CRITICAL R1 fix: database status=5 must mean SUCCESS, not UNKNOWN
        $this->assertEquals(5, RefundStatus::SUCCESS);
        $this->assertNotEquals(5, RefundStatus::UNKNOWN);
        $this->assertEquals(4, RefundStatus::UNKNOWN);

        // Verify label
        $this->assertEquals('退款成功', RefundStatus::label(5));
        $this->assertEquals('未知/待对账', RefundStatus::label(4));
    }

    public function test_legacy_status_2_means_rejected_not_success()
    {
        $this->assertEquals(2, RefundStatus::REJECTED);
        $this->assertNotEquals(2, RefundStatus::SUCCESS);
        $this->assertEquals('已拒绝', RefundStatus::label(2));
    }

    public function test_legacy_status_6_means_cancelled_not_rejected()
    {
        $this->assertEquals(6, RefundStatus::CANCELLED);
        $this->assertNotEquals(6, RefundStatus::REJECTED);
        $this->assertEquals('已取消', RefundStatus::label(6));
    }

    public function test_is_legacy_compatible_for_all_legacy_values()
    {
        $this->assertTrue(RefundStatus::isLegacyCompatible(0));
        $this->assertTrue(RefundStatus::isLegacyCompatible(2));
        $this->assertTrue(RefundStatus::isLegacyCompatible(5));
        $this->assertTrue(RefundStatus::isLegacyCompatible(6));
    }

    // ============================================================
    // R1: refund_no DB UNIQUE
    // ============================================================

    public function test_refund_no_database_unique_constraint_exists()
    {
        $indexes = DB::select("SHOW INDEX FROM order_refunds WHERE Key_name = 'order_refunds_refund_no_unique'");
        $this->assertNotEmpty($indexes);
        $this->assertEquals(0, $indexes[0]->Non_unique);
    }

    public function test_duplicate_refund_no_is_rejected_by_database()
    {
        $service = app(RefundService::class);
        $r1 = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 10.00,
            'reason' => 't1',
            'type' => 1,
        ]);
        $this->assertTrue($r1['success']);

        // Try to insert duplicate refund_no directly
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('order_refunds')->insert([
            'refund_no' => $r1['refund_no'], // duplicate!
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'type' => 1,
            'refund_amount' => 10.00,
            'reason' => 'dup',
            'status' => RefundStatus::REQUESTED,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    // ============================================================
    // R1: provider + provider_refund_no DB UNIQUE
    // ============================================================

    public function test_provider_refund_no_composite_unique_exists()
    {
        $indexes = DB::select("SHOW INDEX FROM order_refunds WHERE Key_name = 'order_refunds_provider_refund_no_unique'");
        $this->assertNotEmpty($indexes);
        $this->assertEquals(0, $indexes[0]->Non_unique);
        // Should have 2 columns: provider + provider_refund_no
        $columns = array_column($indexes, 'Column_name');
        $this->assertContains('provider', $columns);
        $this->assertContains('provider_refund_no', $columns);
    }

    public function test_same_provider_same_refund_no_rejected()
    {
        // Insert first record with provider_refund_no
        DB::table('order_refunds')->insert([
            'refund_no' => 'DUPTEST' . time() . '1',
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'type' => 1,
            'refund_amount' => 10.00,
            'reason' => 't1',
            'status' => RefundStatus::PROCESSING,
            'provider' => 'alipay',
            'provider_refund_no' => '202609110001',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Try same provider + same provider_refund_no
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('order_refunds')->insert([
            'refund_no' => 'DUPTEST' . time() . '2',
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'type' => 1,
            'refund_amount' => 10.00,
            'reason' => 'dup',
            'status' => RefundStatus::PROCESSING,
            'provider' => 'alipay',
            'provider_refund_no' => '202609110001', // same!
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    public function test_different_provider_same_refund_no_allowed()
    {
        DB::table('order_refunds')->insert([
            'refund_no' => 'DIFFPROV' . time() . '1',
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'type' => 1,
            'refund_amount' => 10.00,
            'reason' => 't1',
            'status' => RefundStatus::PROCESSING,
            'provider' => 'alipay',
            'provider_refund_no' => 'SHARED001',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Different provider, same refund number — should be allowed
        DB::table('order_refunds')->insert([
            'refund_no' => 'DIFFPROV' . time() . '2',
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'type' => 1,
            'refund_amount' => 10.00,
            'reason' => 't2',
            'status' => RefundStatus::PROCESSING,
            'provider' => 'wechat',
            'provider_refund_no' => 'SHARED001',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $count = DB::table('order_refunds')->where('provider_refund_no', 'SHARED001')->count();
        $this->assertEquals(2, $count);
    }

    public function test_null_provider_refund_no_allows_multiple_records()
    {
        // Foundation phase: provider_refund_no is NULL until third-party returns
        // MySQL UNIQUE allows multiple NULLs — this is desired behavior
        $service = app(RefundService::class);
        $r1 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 10.00, 'reason' => 't1', 'type' => 1]);
        $r2 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 10.00, 'reason' => 't2', 'type' => 1]);

        $this->assertTrue($r1['success']);
        $this->assertTrue($r2['success']);

        $nullCount = DB::table('order_refunds')
            ->whereIn('id', [$r1['refund_id'], $r2['refund_id']])
            ->whereNull('provider_refund_no')
            ->count();
        $this->assertEquals(2, $nullCount);
    }

    // ============================================================
    // R1: Double Approval Safety
    // ============================================================

    public function test_double_approve_is_safe_second_call_rejected()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 50.00,
            'reason' => 't',
            'type' => 1,
        ]);

        // First approve: REQUESTED → PROCESSING
        $first = $service->approveRefund($r['refund_id']);
        $this->assertTrue($first['success']);

        // Second approve: must be rejected (already PROCESSING)
        $second = $service->approveRefund($r['refund_id']);
        $this->assertFalse($second['success']);
        $this->assertStringContainsString('当前状态不能审核', $second['message']);

        // Verify only one state transition happened
        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        $this->assertEquals(RefundStatus::PROCESSING, $refund->status);
    }

    public function test_cannot_approve_cancelled_refund()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        $service->cancelRefund($r['refund_id'], $this->testUserId);

        $result = $service->approveRefund($r['refund_id']);
        $this->assertFalse($result['success']);
    }

    public function test_cannot_approve_rejected_refund()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        $service->rejectRefund($r['refund_id']);

        $result = $service->approveRefund($r['refund_id']);
        $this->assertFalse($result['success']);
    }

    // ============================================================
    // R1: PROCESSING Presentation (not SUCCESS)
    // ============================================================

    public function test_processing_label_is_not_success()
    {
        $this->assertEquals('处理中', RefundStatus::label(RefundStatus::PROCESSING));
        $this->assertNotEquals('退款成功', RefundStatus::label(RefundStatus::PROCESSING));
        $this->assertEquals('退款成功', RefundStatus::label(RefundStatus::SUCCESS));
    }

    public function test_approve_produces_processing_not_success()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']);

        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        $this->assertEquals(RefundStatus::PROCESSING, $refund->status);
        $this->assertNotEquals(RefundStatus::SUCCESS, $refund->status);
        $this->assertNull($refund->provider_refund_no);
    }

    // ============================================================
    // Amount Safety (preserved from Foundation)
    // ============================================================

    public function test_full_refund_100_from_100_passes()
    {
        $service = app(RefundService::class);
        $result = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 100.00, 'reason' => 'full', 'type' => 1]);
        $this->assertTrue($result['success']);
    }

    public function test_refund_101_from_100_fails()
    {
        $service = app(RefundService::class);
        $result = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 101.00, 'reason' => 'over', 'type' => 1]);
        $this->assertFalse($result['success']);
    }

    public function test_partial_refund_80_then_20_passes()
    {
        $service = app(RefundService::class);
        $r1 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 80.00, 'reason' => 'first', 'type' => 1]);
        $this->assertTrue($r1['success']);
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
        $service->approveRefund($r1['refund_id']); // now PROCESSING

        $r2 = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 21.00, 'reason' => 'over', 'type' => 1]);
        $this->assertFalse($r2['success']);
    }

    // ============================================================
    // Identity (preserved)
    // ============================================================

    public function test_refund_has_payment_identity()
    {
        $service = app(RefundService::class);
        $result = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 'test', 'type' => 1]);
        $this->assertTrue($result['success']);
        $refund = DB::table('order_refunds')->where('id', $result['refund_id'])->first();
        $this->assertEquals($this->testPaymentId, $refund->payment_id);
        $this->assertEquals('alipay', $refund->provider);
    }

    // ============================================================
    // Fake Success Elimination (preserved)
    // ============================================================

    public function test_approve_does_not_produce_fake_success()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']);

        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        $this->assertNotEquals(RefundStatus::SUCCESS, $refund->status);
        $this->assertNull($refund->provider_refund_no);
        $order = DB::table('orders')->where('id', $this->testOrderId)->first();
        $this->assertEquals(1, $order->status);
    }

    public function test_no_ref_time_in_provider_refund_no_after_approve()
    {
        $service = app(RefundService::class);
        $r = $service->createRefund(['order_id' => $this->testOrderId, 'user_id' => $this->testUserId, 'refund_amount' => 50.00, 'reason' => 't', 'type' => 1]);
        $service->approveRefund($r['refund_id']);

        $refund = DB::table('order_refunds')->where('id', $r['refund_id'])->first();
        $this->assertNull($refund->provider_refund_no);
    }
}
