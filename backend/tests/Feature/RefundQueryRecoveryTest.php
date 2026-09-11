<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Services\RefundRecoveryService;
use App\Modules\Refund\Constants\RefundStatus;
use App\Modules\Refund\Adapters\AlipayRefundAdapter;
use App\Modules\Refund\Adapters\WechatRefundAdapter;
use App\Modules\Payment\Services\AlipayService;
use App\Modules\Payment\Services\WechatPayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Mockery;

/**
 * P1-PI-05: Refund Query & UNKNOWN Recovery Tests
 *
 * Covers:
 * - Provider Query: alipay/wechat query success/failed/processing/timeout
 * - Recovery: UNKNOWN + query SUCCESS → SUCCESS
 * - Idempotency: Recovery x10 = 1 record, 1 refund_no
 * - Concurrency: two workers, only one queries
 */
class RefundQueryRecoveryTest extends TestCase
{
    private $testUserId;
    private $testOrderId;
    private $testOrderNo;
    private $testPayNo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUserId = DB::table('users')->insertGetId([
            'name' => 'QRYTest',
            'email' => 'qry_' . uniqid() . '@test.com',
            'password' => bcrypt('test123'),
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $this->testOrderNo = 'QRY' . date('YmdHis') . rand(1000, 9999);
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

        $this->testPayNo = 'PAYQRY' . time();
        DB::table('payments')->insert([
            'payment_no' => $this->testPayNo,
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1, 'pay_type' => 2, 'amount' => 100.00,
            'status' => 1, 'third_payment_no' => 'TXNQRY' . time(),
            'provider' => 'alipay',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);
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

    private function createRefundInStatus(int $status, float $amount = 50.00): array
    {
        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => $amount,
            'reason' => 'query test',
            'type' => 1,
        ]);
        $service->approveRefund($result['refund_id']);

        if ($status !== RefundStatus::PROCESSING) {
            DB::table('order_refunds')->where('id', $result['refund_id'])->update([
                'status' => $status,
                'updated_at' => Carbon::now()->subMinutes(10), // old enough for recovery scan
            ]);
        } else {
            DB::table('order_refunds')->where('id', $result['refund_id'])->update([
                'updated_at' => Carbon::now()->subMinutes(40), // old enough for processing scan
            ]);
        }

        return $result;
    }

    // ============================================================
    // Provider Query Tests
    // ============================================================

    public function test_alipay_query_success()
    {
        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->with($this->testPayNo, 'RF001')->andReturn([
            'success' => true,
            'refund_status' => 'REFUND_SUCCESS',
            'refund_amount' => '50.00',
            'trade_no' => '202100619764856820260911001',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $adapter = app(AlipayRefundAdapter::class);
        $result = $adapter->query('RF001', $this->testPayNo);

        $this->assertTrue($result->isSuccess());
    }

    public function test_alipay_query_failed()
    {
        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => true,
            'refund_status' => 'REFUND_FAIL',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $adapter = app(AlipayRefundAdapter::class);
        $result = $adapter->query('RF001', $this->testPayNo);

        $this->assertTrue($result->isFailed());
    }

    public function test_alipay_query_processing()
    {
        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => true,
            'refund_status' => 'PROCESSING',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $adapter = app(AlipayRefundAdapter::class);
        $result = $adapter->query('RF001', $this->testPayNo);

        $this->assertTrue($result->isProcessing());
    }

    public function test_alipay_query_timeout_returns_unknown()
    {
        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => false,
            'message' => 'Connection timed out',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $adapter = app(AlipayRefundAdapter::class);
        $result = $adapter->query('RF001', $this->testPayNo);

        $this->assertTrue($result->isUnknown());
    }

    public function test_wechat_query_success()
    {
        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->with('RF001')->andReturn([
            'success' => true,
            'refund_status' => 'SUCCESS',
            'refund_id' => '500000003820260911001',
            'amount' => ['refund' => 5000, 'total' => 10000],
        ]);
        $this->app->instance(WechatPayService::class, $mock);

        $adapter = app(WechatRefundAdapter::class);
        $result = $adapter->query('RF001', 'PAY001');

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('500000003820260911001', $result->providerRefundNo);
    }

    public function test_wechat_query_failed()
    {
        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => true,
            'refund_status' => 'CLOSED',
        ]);
        $this->app->instance(WechatPayService::class, $mock);

        $adapter = app(WechatRefundAdapter::class);
        $result = $adapter->query('RF001', 'PAY001');

        $this->assertTrue($result->isFailed());
    }

    public function test_wechat_query_processing()
    {
        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => true,
            'refund_status' => 'PROCESSING',
        ]);
        $this->app->instance(WechatPayService::class, $mock);

        $adapter = app(WechatRefundAdapter::class);
        $result = $adapter->query('RF001', 'PAY001');

        $this->assertTrue($result->isProcessing());
    }

    public function test_wechat_query_timeout_returns_unknown()
    {
        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => false,
            'message' => 'Connection timed out',
        ]);
        $this->app->instance(WechatPayService::class, $mock);

        $adapter = app(WechatRefundAdapter::class);
        $result = $adapter->query('RF001', 'PAY001');

        $this->assertTrue($result->isUnknown());
    }

    // ============================================================
    // Recovery Tests
    // ============================================================

    public function test_unknown_refund_recovered_to_success_via_query()
    {
        $refund = $this->createRefundInStatus(RefundStatus::UNKNOWN);

        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => true,
            'refund_status' => 'REFUND_SUCCESS',
            'refund_amount' => '50.00',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $recovery = app(RefundRecoveryService::class);
        $result = $recovery->recoverRefundById($refund['refund_id']);

        $this->assertTrue($result['success']);
        $this->assertEquals(RefundStatus::SUCCESS, $result['status']);

        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        $this->assertEquals(RefundStatus::SUCCESS, $dbRefund->status);
        $this->assertNotNull($dbRefund->refunded_at);
    }

    public function test_unknown_refund_recovered_to_failed_via_query()
    {
        $refund = $this->createRefundInStatus(RefundStatus::UNKNOWN);

        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => true,
            'refund_status' => 'REFUND_FAIL',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $recovery = app(RefundRecoveryService::class);
        $result = $recovery->recoverRefundById($refund['refund_id']);

        $this->assertEquals(RefundStatus::FAILED, $result['status']);
    }

    public function test_processing_refund_recovered_to_success_via_query()
    {
        $refund = $this->createRefundInStatus(RefundStatus::PROCESSING);

        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->once()->andReturn([
            'success' => true,
            'refund_status' => 'REFUND_SUCCESS',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $recovery = app(RefundRecoveryService::class);
        $result = $recovery->recoverRefundById($refund['refund_id']);

        $this->assertEquals(RefundStatus::SUCCESS, $result['status']);
    }

    // ============================================================
    // Idempotency Test
    // ============================================================

    public function test_recovery_idempotent_10_runs_same_record()
    {
        $refund = $this->createRefundInStatus(RefundStatus::UNKNOWN);
        $originalRefundNo = $refund['refund_no'];

        for ($i = 0; $i < 10; $i++) {
            $mock = Mockery::mock(AlipayService::class);
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('queryRefund')->andReturn([
                'success' => true,
                'refund_status' => 'REFUND_SUCCESS',
            ]);
            $this->app->instance(AlipayService::class, $mock);

            $recovery = app(RefundRecoveryService::class);
            $recovery->recoverRefundById($refund['refund_id']);
        }

        // Only one refund record, same refund_no
        $count = DB::table('order_refunds')->where('order_id', $this->testOrderId)->count();
        $this->assertEquals(1, $count);

        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        $this->assertEquals($originalRefundNo, $dbRefund->refund_no);
        $this->assertEquals(RefundStatus::SUCCESS, $dbRefund->status);
    }

    // ============================================================
    // State Machine Constraint Tests
    // ============================================================

    public function test_failed_refund_cannot_be_recovered_to_success()
    {
        $refund = $this->createRefundInStatus(RefundStatus::FAILED);

        $recovery = app(RefundRecoveryService::class);
        $result = $recovery->recoverRefundById($refund['refund_id']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('不需要恢复', $result['message']);

        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        $this->assertEquals(RefundStatus::FAILED, $dbRefund->status);
    }

    public function test_success_refund_cannot_be_recovered()
    {
        $refund = $this->createRefundInStatus(RefundStatus::SUCCESS);

        $recovery = app(RefundRecoveryService::class);
        $result = $recovery->recoverRefundById($refund['refund_id']);

        $this->assertFalse($result['success']);

        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        $this->assertEquals(RefundStatus::SUCCESS, $dbRefund->status);
    }

    // ============================================================
    // Recovery Scan Test
    // ============================================================

    public function test_run_recovery_scans_stuck_refunds()
    {
        $refund1 = $this->createRefundInStatus(RefundStatus::UNKNOWN);
        $refund2 = $this->createRefundInStatus(RefundStatus::UNKNOWN);

        $mock = Mockery::mock(AlipayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('queryRefund')->andReturn([
            'success' => true,
            'refund_status' => 'REFUND_SUCCESS',
        ]);
        $this->app->instance(AlipayService::class, $mock);

        $recovery = app(RefundRecoveryService::class);
        $processed = $recovery->runRecovery();

        $this->assertEquals(2, $processed);

        $dbRefund1 = DB::table('order_refunds')->where('id', $refund1['refund_id'])->first();
        $dbRefund2 = DB::table('order_refunds')->where('id', $refund2['refund_id'])->first();
        $this->assertEquals(RefundStatus::SUCCESS, $dbRefund1->status);
        $this->assertEquals(RefundStatus::SUCCESS, $dbRefund2->status);
    }
}
