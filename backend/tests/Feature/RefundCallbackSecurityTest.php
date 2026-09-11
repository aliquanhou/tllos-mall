<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Refund\Services\RefundCallbackService;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Constants\RefundStatus;
use App\Modules\Payment\Services\AlipayService;
use App\Modules\Payment\Services\WechatPayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Mockery;

/**
 * P1-PI-03: Refund Callback Security & State Machine Tests
 *
 * 12 tests covering:
 * 1. alipay signature invalid rejected
 * 2. wechat signature invalid rejected
 * 3. refund_no exact match
 * 4. order_no cannot match refund
 * 5. amount mismatch rejected
 * 6. processing -> success
 * 7. processing -> failed
 * 8. success duplicate callback no change
 * 9. failed cannot become success
 * 10. callback retry idempotent
 * 11. provider identity preserved
 * 12. fake refund callback cannot create refund
 */
class RefundCallbackSecurityTest extends TestCase
{
    private $testUserId;
    private $testOrderId;
    private $testOrderNo;
    private $testPayNo;
    private $testRefundId;
    private $testRefundNo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUserId = DB::table('users')->insertGetId([
            'name' => 'CBTest',
            'email' => 'cb_' . uniqid() . '@test.com',
            'password' => bcrypt('test123'),
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $this->testOrderNo = 'CB' . date('YmdHis') . rand(1000, 9999);
        $this->testOrderId = DB::table('orders')->insertGetId([
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'total_amount' => 100.00, 'pay_amount' => 100.00,
            'status' => 1, 'pay_type' => 2,
            'receiver_name' => 'T', 'receiver_mobile' => '13800138000',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => 'GD', 'city_name' => 'HZ', 'district_name' => 'DY',
            'receiver_address' => 'T',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $this->testPayNo = 'PAYCB' . time();
        DB::table('payments')->insert([
            'payment_no' => $this->testPayNo,
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1, 'pay_type' => 2, 'amount' => 100.00,
            'status' => 1, 'third_payment_no' => 'TXNCB' . time(),
            'provider' => 'alipay',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        // Create and approve a refund -> PROCESSING
        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => 50.00,
            'reason' => 'callback test',
            'type' => 1,
        ]);
        $this->testRefundId = $result['refund_id'];
        $this->testRefundNo = $result['refund_no'];
        $service->approveRefund($this->testRefundId);
    }

    protected function tearDown(): void
    {
        DB::table('order_refunds')->where('user_id', $this->testUserId)->delete();
        DB::table('payments')->where('user_id', $this->testUserId)->delete();
        DB::table('orders')->where('user_id', $this->testUserId)->delete();
        DB::table('users')->where('id', $this->testUserId)->delete();
        Mockery::close();
        parent::tearDown();
    }

    private function mockAlipayVerify(bool $success, string $message = ''): void
    {
        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('verifyNotify')->once()->andReturn([
            'success' => $success,
            'message' => $message,
        ]);
        $this->app->instance(AlipayService::class, $mock);
    }

    private function mockWechatVerify(bool $success, string $message = ''): void
    {
        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('verifyNotify')->once()->andReturn([
            'success' => $success,
            'message' => $message,
        ]);
        $this->app->instance(WechatPayService::class, $mock);
    }

    // ============================================================
    // Test 1: Alipay signature invalid -> rejected
    // ============================================================
    public function test_alipay_signature_invalid_rejected()
    {
        $this->mockAlipayVerify(false, 'invalid signature');

        $service = app(RefundCallbackService::class);
        $result = $service->handleAlipayCallback([
            'out_request_no' => $this->testRefundNo,
            'refund_amount' => '50.00',
            'refund_status' => 'REFUND_SUCCESS',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(400, $result['http_code']);

        // Refund status unchanged
        $dbRefund = DB::table('order_refunds')->where('id', $this->testRefundId)->first();
        $this->assertEquals(RefundStatus::PROCESSING, $dbRefund->status);
    }

    // ============================================================
    // Test 2: WeChat signature invalid -> rejected
    // ============================================================
    public function test_wechat_signature_invalid_rejected()
    {
        $this->mockWechatVerify(false, 'invalid signature');

        $service = app(RefundCallbackService::class);
        $result = $service->handleWechatCallback([
            'out_refund_no' => $this->testRefundNo,
            'refund_status' => 'SUCCESS',
            'amount' => ['refund' => 5000],
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(400, $result['http_code']);

        $dbRefund = DB::table('order_refunds')->where('id', $this->testRefundId)->first();
        $this->assertEquals(RefundStatus::PROCESSING, $dbRefund->status);
    }

    // ============================================================
    // Test 3: refund_no exact match works
    // ============================================================
    public function test_refund_no_exact_match()
    {
        $this->mockAlipayVerify(true);

        $service = app(RefundCallbackService::class);
        $result = $service->handleAlipayCallback([
            'out_request_no' => $this->testRefundNo,
            'refund_amount' => '50.00',
            'refund_status' => 'REFUND_SUCCESS',
            'trade_no' => '202100619764856820260911001',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(RefundStatus::SUCCESS, $result['status']);

        $dbRefund = DB::table('order_refunds')->where('id', $this->testRefundId)->first();
        $this->assertEquals(RefundStatus::SUCCESS, $dbRefund->status);
        $this->assertNotNull($dbRefund->refunded_at);
    }

    // ============================================================
    // Test 4: order_no cannot match refund (identity must be refund_no)
    // ============================================================
    public function test_order_no_cannot_match_refund()
    {
        $this->mockAlipayVerify(true);

        $service = app(RefundCallbackService::class);
        // Use order_no instead of refund_no — must NOT match
        $result = $service->handleAlipayCallback([
            'out_request_no' => $this->testOrderNo,  // order_no, not refund_no
            'refund_amount' => '50.00',
            'refund_status' => 'REFUND_SUCCESS',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['http_code']);

        // Refund unchanged
        $dbRefund = DB::table('order_refunds')->where('id', $this->testRefundId)->first();
        $this->assertEquals(RefundStatus::PROCESSING, $dbRefund->status);
    }

    // ============================================================
    // Test 5: amount mismatch -> rejected
    // ============================================================
    public function test_amount_mismatch_rejected()
    {
        $this->mockAlipayVerify(true);

        $service = app(RefundCallbackService::class);
        $result = $service->handleAlipayCallback([
            'out_request_no' => $this->testRefundNo,
            'refund_amount' => '100.00',  // local is 50.00
            'refund_status' => 'REFUND_SUCCESS',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(400, $result['http_code']);

        $dbRefund = DB::table('order_refunds')->where('id', $this->testRefundId)->first();
        $this->assertEquals(RefundStatus::PROCESSING, $dbRefund->status);
    }

    // ============================================================
    // Test 6: PROCESSING -> SUCCESS
    // ============================================================
    public function test_processing_to_success()
    {
        $this->mockAlipayVerify(true);

        $service = app(RefundCallbackService::class);
        $result = $service->handleAlipayCallback([
            'out_request_no' => $this->testRefundNo,
            'refund_amount' => '50.00',
            'refund_status' => 'REFUND_SUCCESS',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(RefundStatus::SUCCESS, $result['status']);

        $dbRefund = DB::table('order_refunds')->where('id', $this->testRefundId)->first();
        $this->assertEquals(RefundStatus::SUCCESS, $dbRefund->status);
    }

    // ============================================================
    // Test 7: PROCESSING -> FAILED
    // ============================================================
    public function test_processing_to_failed()
    {
        $this->mockAlipayVerify(true);

        $service = app(RefundCallbackService::class);
        $result = $service->handleAlipayCallback([
            'out_request_no' => $this->testRefundNo,
            'refund_amount' => '50.00',
            'refund_status' => 'REFUND_FAIL',
            'fail_reason' => 'ACQ.SYSTEM_ERROR',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals(RefundStatus::FAILED, $result['status']);

        $dbRefund = DB::table('order_refunds')->where('id', $this->testRefundId)->first();
        $this->assertEquals(RefundStatus::FAILED, $dbRefund->status);
        $this->assertStringContainsString('ACQ.SYSTEM_ERROR', $dbRefund->failure_reason);
    }

    // ============================================================
    // Test 8: SUCCESS duplicate callback -> no change (idempotent)
    // ============================================================
    public function test_success_duplicate_callback_no_change()
    {
        $this->mockAlipayVerify(true);
        $service = app(RefundCallbackService::class);

        // First callback: PROCESSING -> SUCCESS
        $result1 = $service->handleAlipayCallback([
            'out_request_no' => $this->testRefundNo,
            'refund_amount' => '50.00',
            'refund_status' => 'REFUND_SUCCESS',
        ]);
        $this->assertTrue($result1['success']);
        $this->assertEquals(RefundStatus::SUCCESS, $result1['status']);

        $refundedAt = DB::table('order_refunds')->where('id', $this->testRefundId)->value('refunded_at');

        // Second callback: duplicate, should be NO-OP
        $this->mockAlipayVerify(true);
        $result2 = $service->handleAlipayCallback([
            'out_request_no' => $this->testRefundNo,
            'refund_amount' => '50.00',
            'refund_status' => 'REFUND_SUCCESS',
        ]);

        $this->assertTrue($result2['success']);
        $this->assertTrue($result2['idempotent']);
        $this->assertEquals('重复通知，状态未变化', $result2['message']);

        // refunded_at unchanged
        $refundedAt2 = DB::table('order_refunds')->where('id', $this->testRefundId)->value('refunded_at');
        $this->assertEquals($refundedAt, $refundedAt2);
    }

    // ============================================================
    // Test 9: FAILED cannot become SUCCESS
    // ============================================================
    public function test_failed_cannot_become_success()
    {
        // First: PROCESSING -> FAILED
        $this->mockAlipayVerify(true);
        $service = app(RefundCallbackService::class);
        $service->handleAlipayCallback([
            'out_request_no' => $this->testRefundNo,
            'refund_amount' => '50.00',
            'refund_status' => 'REFUND_FAIL',
        ]);

        // Second: try FAILED -> SUCCESS (must be rejected)
        $this->mockAlipayVerify(true);
        $result = $service->handleAlipayCallback([
            'out_request_no' => $this->testRefundNo,
            'refund_amount' => '50.00',
            'refund_status' => 'REFUND_SUCCESS',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(409, $result['http_code']);

        $dbRefund = DB::table('order_refunds')->where('id', $this->testRefundId)->first();
        $this->assertEquals(RefundStatus::FAILED, $dbRefund->status);
    }

    // ============================================================
    // Test 10: callback retry idempotent (10x same callback)
    // ============================================================
    public function test_callback_retry_idempotent()
    {
        $service = app(RefundCallbackService::class);

        for ($i = 0; $i < 10; $i++) {
            $this->mockAlipayVerify(true);
            $result = $service->handleAlipayCallback([
                'out_request_no' => $this->testRefundNo,
                'refund_amount' => '50.00',
                'refund_status' => 'REFUND_SUCCESS',
            ]);
            $this->assertTrue($result['success']);
        }

        // Only one refund record, status SUCCESS
        $count = DB::table('order_refunds')->where('order_id', $this->testOrderId)->count();
        $this->assertEquals(1, $count);

        $dbRefund = DB::table('order_refunds')->where('id', $this->testRefundId)->first();
        $this->assertEquals(RefundStatus::SUCCESS, $dbRefund->status);
    }

    // ============================================================
    // Test 11: provider identity preserved on callback
    // ============================================================
    public function test_provider_identity_preserved()
    {
        $this->mockAlipayVerify(true);

        $service = app(RefundCallbackService::class);
        $service->handleAlipayCallback([
            'out_request_no' => $this->testRefundNo,
            'refund_amount' => '50.00',
            'refund_status' => 'REFUND_SUCCESS',
            'trade_no' => '202100619764856820260911099',
        ]);

        $dbRefund = DB::table('order_refunds')->where('id', $this->testRefundId)->first();
        $this->assertEquals('202100619764856820260911099', $dbRefund->provider_transaction_no);
        // Alipay has no independent refund_id → provider_refund_no stays as-is (not forged)
        $this->assertEmpty($dbRefund->provider_refund_no);
    }

    // ============================================================
    // Test 12: fake refund callback cannot create refund
    // ============================================================
    public function test_fake_refund_callback_cannot_create_refund()
    {
        $this->mockAlipayVerify(true);

        $service = app(RefundCallbackService::class);
        $result = $service->handleAlipayCallback([
            'out_request_no' => 'FAKE_REFUND_NO_12345',
            'refund_amount' => '999.00',
            'refund_status' => 'REFUND_SUCCESS',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['http_code']);

        // No new refund created
        $count = DB::table('order_refunds')->where('refund_no', 'FAKE_REFUND_NO_12345')->count();
        $this->assertEquals(0, $count);
    }
}
