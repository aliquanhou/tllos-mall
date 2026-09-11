<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Constants\RefundStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * P1-REFUND-PRECONDITION-R2 Tests
 *
 * Focuses on the three closure items:
 * 1. Refund Retry Idempotency: same refund_no × 10 → 1 local refund, 1 provider identity
 * 2. Legacy order_no fallback is marked LEGACY ONLY
 * 3. Alipay Identity Mapping field-level verification
 */
class RefundPreconditionR2Test extends TestCase
{
    private $testUserId;
    private $testOrderId;
    private $testOrderNo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUserId = DB::table('users')->insertGetId([
            'name' => 'R2TestUser',
            'email' => 'r2_' . uniqid() . '@test.com',
            'password' => bcrypt('test123456'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->testOrderNo = 'R2' . date('YmdHis') . rand(1000, 9999);
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

        // Create a paid alipay payment for this order
        $this->payNo = 'PAYR2' . time();
        $this->txnId = 'TXNR2' . time();
        DB::table('payments')->insert([
            'payment_no' => $this->payNo,
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1,
            'pay_type' => 2,
            'amount' => 100.00,
            'status' => 1,
            'third_payment_no' => $this->txnId,
            'provider' => 'alipay',
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
    // 1. Refund Retry Idempotency (CORE R2 TEST)
    // ============================================================

    public function test_same_refund_no_retry_10_times_only_one_record()
    {
        $service = app(RefundService::class);

        // Step 1: Create refund
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 50.00,
            'reason' => 'retry test',
            'type' => 1,
        ]);
        $this->assertTrue($result['success']);
        $refundId = $result['refund_id'];
        $refundNo = $result['refund_no'];

        // Step 2: Approve → PROCESSING
        $approve = $service->approveRefund($refundId);
        $this->assertTrue($approve['success']);

        // Step 3: Simulate timeout → UNKNOWN
        $unknown = $service->markAsUnknown($refundId, 'simulated timeout');
        $this->assertTrue($unknown['success']);

        // Step 4: Retry 10 times — must reuse SAME refund_no, must NOT create new records
        $refundCountBefore = DB::table('order_refunds')->where('order_id', $this->testOrderId)->count();
        $this->assertEquals(1, $refundCountBefore);

        for ($i = 1; $i <= 10; $i++) {
            $retry = $service->retryRefund($refundId);
            $this->assertTrue($retry['success'], "Retry $i failed");
            $this->assertEquals($refundNo, $retry['refund_no'], "Retry $i changed refund_no!");
        }

        // Step 5: Verify still only 1 refund record
        $refundCountAfter = DB::table('order_refunds')->where('order_id', $this->testOrderId)->count();
        $this->assertEquals(1, $refundCountAfter, 'Retry created duplicate refund records!');

        // Step 6: Verify refund_no unchanged
        $refund = DB::table('order_refunds')->where('id', $refundId)->first();
        $this->assertEquals($refundNo, $refund->refund_no);

        // Step 7: Verify attempts incremented to 11 (1 initial + 10 retries)
        $this->assertEquals(11, $refund->attempts);

        // Step 8: Verify status remains UNKNOWN (retry doesn't change status without provider response)
        $this->assertEquals(RefundStatus::UNKNOWN, $refund->status);
    }

    public function test_retry_idempotency_processing_state()
    {
        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 30.00,
            'reason' => 't',
            'type' => 1,
        ]);
        $service->approveRefund($result['refund_id']);

        // Retry from PROCESSING state
        $retry = $service->retryRefund($result['refund_id']);
        $this->assertTrue($retry['success']);
        $this->assertEquals($result['refund_no'], $retry['refund_no']);

        // Still only 1 record
        $this->assertEquals(1, DB::table('order_refunds')->where('order_id', $this->testOrderId)->count());
    }

    public function test_retry_rejected_for_non_retryable_states()
    {
        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 20.00,
            'reason' => 't',
            'type' => 1,
        ]);

        // REQUESTED state cannot be retried (must approve first)
        $retry = $service->retryRefund($result['refund_id']);
        $this->assertFalse($retry['success']);
        $this->assertStringContainsString('不支持重试', $retry['message']);
    }

    public function test_get_refund_by_no_returns_exact_record()
    {
        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 10.00,
            'reason' => 't',
            'type' => 1,
        ]);

        $found = $service->getRefundByNo($result['refund_no']);
        $this->assertNotNull($found);
        $this->assertEquals($result['refund_id'], $found->id);
        $this->assertEquals($result['refund_no'], $found->refund_no);

        // Non-existent refund_no returns null
        $this->assertNull($service->getRefundByNo('NONEXISTENT' . time()));
    }

    // ============================================================
    // 2. Legacy order_no fallback is LEGACY ONLY
    // ============================================================

    public function test_legacy_fallback_code_path_is_marked()
    {
        // Verify the LEGACY ONLY marker exists in source code
        $source = file_get_contents(app_path('Modules/Payment/Controllers/PaymentNotifyController.php'));
        $this->assertStringContainsString('LEGACY ONLY', $source);
        $this->assertStringContainsString('DO NOT USE FOR NEW PROVIDER INTEGRATION', $source);
        $this->assertStringContainsString('payment_no as out_trade_no', $source);
    }

    public function test_new_payment_uses_payment_no_not_order_no()
    {
        // Verify PaymentController uses payment_no for out_trade_no
        $source = file_get_contents(app_path('Modules/Payment/Controllers/PaymentController.php'));
        $this->assertStringContainsString("'out_trade_no' => \$payNo", $source);
        $this->assertStringNotContainsString("'out_trade_no' => \$order->order_no", $source);
    }

    // ============================================================
    // 3. Alipay Identity Mapping field-level verification
    // ============================================================

    public function test_alipay_identity_mapping_fields_exist()
    {
        // Verify payments table has provider and third_payment_no
        $columns = DB::select("SHOW COLUMNS FROM payments WHERE Field IN ('provider', 'third_payment_no', 'payment_no')");
        $fieldNames = array_column($columns, 'Field');
        $this->assertContains('provider', $fieldNames);
        $this->assertContains('third_payment_no', $fieldNames);
        $this->assertContains('payment_no', $fieldNames);

        // Verify order_refunds has provider identity fields
        $refundColumns = DB::select("SHOW COLUMNS FROM order_refunds WHERE Field IN ('provider', 'provider_transaction_no', 'provider_refund_no', 'payment_no', 'refund_no')");
        $refundFieldNames = array_column($refundColumns, 'Field');
        $this->assertContains('provider', $refundFieldNames);
        $this->assertContains('provider_transaction_no', $refundFieldNames);
        $this->assertContains('provider_refund_no', $refundFieldNames);
        $this->assertContains('payment_no', $refundFieldNames);
        $this->assertContains('refund_no', $refundFieldNames);
    }

    public function test_alipay_identity_mapping_relationship()
    {
        // Create a refund and verify identity chain: Order → Payment → Refund
        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 25.00,
            'reason' => 'identity test',
            'type' => 1,
        ]);

        $refund = DB::table('order_refunds')->where('id', $result['refund_id'])->first();

        // TLL payment_no → Alipay out_trade_no
        $this->assertEquals($this->payNo, $refund->payment_no);

        // Alipay trade_no → TLL payments.third_payment_no
        $this->assertEquals($this->txnId, $refund->provider_transaction_no);

        // Provider = alipay
        $this->assertEquals('alipay', $refund->provider);

        // TLL refund_no → Alipay out_request_no (will be used in Provider Integration)
        $this->assertNotNull($refund->refund_no);
        $this->assertStringStartsWith('RF', $refund->refund_no);

        // provider_refund_no must be NULL until provider returns
        $this->assertNull($refund->provider_refund_no);
    }

    public function test_provider_refund_no_unique_constraint_exists()
    {
        $indexes = DB::select("SHOW INDEX FROM order_refunds WHERE Key_name LIKE '%provider_refund%'");
        $this->assertNotEmpty($indexes);
        // UNIQUE(provider, provider_refund_no) — composite unique
        $uniqueIndex = collect($indexes)->first(fn($i) => $i->Non_unique == 0);
        $this->assertNotNull($uniqueIndex, 'provider_refund_no unique constraint not found');
    }
}
