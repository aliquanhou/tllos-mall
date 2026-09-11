<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Refund\Services\RefundService;
use App\Modules\Refund\Constants\RefundStatus;
use App\Modules\Refund\Adapters\WechatRefundAdapter;
use App\Modules\Refund\Adapters\RefundProviderFactory;
use App\Modules\Refund\Contracts\RefundProviderResult;
use App\Modules\Payment\Services\WechatPayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Mockery;

/**
 * P1-PI-04: WeChat Refund Implementation Tests
 *
 * 12 tests covering:
 * 1. wechat adapter resolves
 * 2. v3 request body mapping
 * 3. out_refund_no == refund_no
 * 4. payment_no mapped correctly
 * 5. HTTP 200 becomes PROCESSING (not SUCCESS)
 * 6. refund_id saved as provider_refund_no
 * 7. transaction_id preserved
 * 8. provider failure becomes FAILED
 * 9. timeout becomes UNKNOWN
 * 10. retry keeps same refund_no
 * 11. production without config fails closed
 * 12. no database transaction wraps provider call
 */
class WechatRefundImplementationTest extends TestCase
{
    private $testUserId;
    private $testOrderId;
    private $testOrderNo;
    private $testPayNo;
    private $testTxnId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUserId = DB::table('users')->insertGetId([
            'name' => 'WXTest',
            'email' => 'wx_' . uniqid() . '@test.com',
            'password' => bcrypt('test123'),
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $this->testOrderNo = 'WX' . date('YmdHis') . rand(1000, 9999);
        $this->testOrderId = DB::table('orders')->insertGetId([
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'total_amount' => 100.00, 'pay_amount' => 100.00,
            'status' => 1, 'pay_type' => 1, // wechat
            'receiver_name' => 'T', 'receiver_mobile' => '13800138000',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => 'GD', 'city_name' => 'HZ', 'district_name' => 'DY',
            'receiver_address' => 'T',
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);

        $this->testPayNo = 'PAYWX' . time();
        $this->testTxnId = '420000' . time() . '0001';
        DB::table('payments')->insert([
            'payment_no' => $this->testPayNo,
            'order_no' => $this->testOrderNo,
            'user_id' => $this->testUserId,
            'type' => 1, 'pay_type' => 1, 'amount' => 100.00,
            'status' => 1, 'third_payment_no' => $this->testTxnId,
            'provider' => 'wechat',
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

    private function createAndApproveRefund(float $amount = 50.00): array
    {
        $service = app(RefundService::class);
        $result = $service->createRefund([
            'order_id' => $this->testOrderId,
            'user_id' => $this->testUserId,
            'refund_amount' => $amount,
            'reason' => 'wechat test',
            'type' => 1,
        ]);
        $service->approveRefund($result['refund_id']);
        return $result;
    }

    private function mockWechatConfigured(array $refundResult): void
    {
        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('refund')->once()->andReturn($refundResult);
        $this->app->instance(WechatPayService::class, $mock);
    }

    // ============================================================
    // Test 1: WeChat adapter resolves from factory
    // ============================================================
    public function test_wechat_adapter_resolves()
    {
        $factory = app(RefundProviderFactory::class);
        $adapter = $factory->make('wechat');

        $this->assertInstanceOf(WechatRefundAdapter::class, $adapter);
        $this->assertEquals('wechat', $adapter->getProviderName());
    }

    // ============================================================
    // Test 2: V3 request body mapping
    // ============================================================
    public function test_v3_request_body_mapping()
    {
        $capturedParams = null;
        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('refund')->once()->with(Mockery::on(function ($params) use (&$capturedParams) {
            $capturedParams = $params;
            return true;
        }))->andReturn([
            'success' => true,
            'refund_id' => '500000003820260911001',
            'out_refund_no' => 'RF_TEST_001',
        ]);
        $this->app->instance(WechatPayService::class, $mock);

        $adapter = app(WechatRefundAdapter::class);
        $adapter->refund([
            'out_trade_no' => $this->testPayNo,
            'out_request_no' => 'RF_TEST_001',
            'amount' => '50.00',
            'payment_amount' => '100.00',
            'provider_transaction_no' => $this->testTxnId,
            'reason' => 'test',
            'notify_url' => 'https://mall.tllos.com/api/v1/payment/refund-notify/wechat',
        ]);

        $this->assertNotNull($capturedParams);
        $this->assertEquals($this->testPayNo, $capturedParams['out_trade_no']);
        $this->assertEquals('RF_TEST_001', $capturedParams['out_refund_no']);
        $this->assertEquals('50.00', $capturedParams['amount']);
        $this->assertEquals('100.00', $capturedParams['total_amount']);
        $this->assertEquals($this->testTxnId, $capturedParams['provider_transaction_no']);
    }

    // ============================================================
    // Test 3: out_refund_no == refund_no
    // ============================================================
    public function test_out_refund_no_equals_refund_no()
    {
        $refund = $this->createAndApproveRefund(50.00);

        $capturedParams = null;
        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('refund')->once()->with(Mockery::on(function ($params) use (&$capturedParams) {
            $capturedParams = $params;
            return true;
        }))->andReturn([
            'success' => true,
            'refund_id' => '500000003820260911002',
        ]);
        $this->app->instance(WechatPayService::class, $mock);

        $service = app(RefundService::class);
        $service->initiateProviderRefund($refund['refund_id']);

        $this->assertEquals($refund['refund_no'], $capturedParams['out_refund_no']);
    }

    // ============================================================
    // Test 4: payment_no mapped correctly
    // ============================================================
    public function test_payment_no_mapped_correctly()
    {
        $refund = $this->createAndApproveRefund(50.00);

        $capturedParams = null;
        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('refund')->once()->with(Mockery::on(function ($params) use (&$capturedParams) {
            $capturedParams = $params;
            return true;
        }))->andReturn([
            'success' => true,
            'refund_id' => '500000003820260911003',
        ]);
        $this->app->instance(WechatPayService::class, $mock);

        $service = app(RefundService::class);
        $service->initiateProviderRefund($refund['refund_id']);

        $this->assertEquals($this->testPayNo, $capturedParams['out_trade_no']);
    }

    // ============================================================
    // Test 5: HTTP 200 becomes PROCESSING (not SUCCESS)
    // ============================================================
    public function test_http_200_becomes_processing_not_success()
    {
        $refund = $this->createAndApproveRefund(50.00);

        $this->mockWechatConfigured([
            'success' => true,
            'refund_id' => '500000003820260911004',
        ]);

        $service = app(RefundService::class);
        $result = $service->initiateProviderRefund($refund['refund_id']);

        // WeChat is async: HTTP 200 = request accepted = PROCESSING, NOT SUCCESS
        $this->assertTrue($result['success']);
        $this->assertEquals(RefundStatus::PROCESSING, $result['status']);

        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        $this->assertEquals(RefundStatus::PROCESSING, $dbRefund->status);
        $this->assertNull($dbRefund->refunded_at); // Not refunded yet
    }

    // ============================================================
    // Test 6: refund_id saved as provider_refund_no
    // ============================================================
    public function test_refund_id_saved_as_provider_refund_no()
    {
        $refund = $this->createAndApproveRefund(50.00);
        $wechatRefundId = '500000003820260911005';

        $this->mockWechatConfigured([
            'success' => true,
            'refund_id' => $wechatRefundId,
        ]);

        $service = app(RefundService::class);
        $service->initiateProviderRefund($refund['refund_id']);

        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        $this->assertEquals($wechatRefundId, $dbRefund->provider_refund_no);
    }

    // ============================================================
    // Test 7: transaction_id preserved (from local payment)
    // ============================================================
    public function test_transaction_id_preserved()
    {
        $refund = $this->createAndApproveRefund(50.00);

        $this->mockWechatConfigured([
            'success' => true,
            'refund_id' => '500000003820260911006',
            // Note: WeChat refund response does NOT include transaction_id
        ]);

        $service = app(RefundService::class);
        $service->initiateProviderRefund($refund['refund_id']);

        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        // provider_transaction_no must come from local payment.third_payment_no
        $this->assertEquals($this->testTxnId, $dbRefund->provider_transaction_no);
    }

    // ============================================================
    // Test 8: provider failure becomes FAILED
    // ============================================================
    public function test_provider_failure_becomes_failed()
    {
        $refund = $this->createAndApproveRefund(50.00);

        $this->mockWechatConfigured([
            'success' => false,
            'message' => 'ACQ.TRADE_NOT_EXIST',
        ]);

        $service = app(RefundService::class);
        $result = $service->initiateProviderRefund($refund['refund_id']);

        $this->assertFalse($result['success']);
        $this->assertEquals(RefundStatus::FAILED, $result['status']);

        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        $this->assertEquals(RefundStatus::FAILED, $dbRefund->status);
        $this->assertStringContainsString('ACQ.TRADE_NOT_EXIST', $dbRefund->failure_reason);
    }

    // ============================================================
    // Test 9: timeout becomes UNKNOWN (not FAILED)
    // ============================================================
    public function test_timeout_becomes_unknown()
    {
        $refund = $this->createAndApproveRefund(50.00);

        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(true);
        $mock->shouldReceive('refund')->once()->andThrow(new \RuntimeException('Connection timed out'));
        $this->app->instance(WechatPayService::class, $mock);

        $service = app(RefundService::class);
        $result = $service->initiateProviderRefund($refund['refund_id']);

        $this->assertFalse($result['success']);
        $this->assertEquals(RefundStatus::UNKNOWN, $result['status']);

        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        $this->assertEquals(RefundStatus::UNKNOWN, $dbRefund->status);
        $this->assertStringContainsString('timed', strtolower($dbRefund->failure_reason));
    }

    // ============================================================
    // Test 10: retry keeps same refund_no (idempotent)
    // ============================================================
    public function test_retry_keeps_same_refund_no()
    {
        $refund = $this->createAndApproveRefund(50.00);
        $originalRefundNo = $refund['refund_no'];

        // First: timeout -> UNKNOWN
        $mock1 = Mockery::mock(WechatPayService::class);
        $mock1->shouldReceive('isConfigured')->andReturn(true);
        $mock1->shouldReceive('refund')->once()->andThrow(new \RuntimeException('timeout'));
        $this->app->instance(WechatPayService::class, $mock1);

        $service = app(RefundService::class);
        $service->initiateProviderRefund($refund['refund_id']);

        // Retry x10: still same refund_no, same record
        for ($i = 0; $i < 10; $i++) {
            $mockN = Mockery::mock(WechatPayService::class);
            $mockN->shouldReceive('isConfigured')->andReturn(true);
            $mockN->shouldReceive('refund')->once()->andThrow(new \RuntimeException('timeout'));
            $this->app->instance(WechatPayService::class, $mockN);
            $service->initiateProviderRefund($refund['refund_id']);
        }

        // Only one refund record, same refund_no
        $count = DB::table('order_refunds')->where('order_id', $this->testOrderId)->count();
        $this->assertEquals(1, $count);

        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        $this->assertEquals($originalRefundNo, $dbRefund->refund_no);
        $this->assertEquals(RefundStatus::UNKNOWN, $dbRefund->status);
    }

    // ============================================================
    // Test 11: production without config fails closed
    // ============================================================
    public function test_production_without_config_fails_closed()
    {
        $refund = $this->createAndApproveRefund(50.00);

        $mock = Mockery::mock(WechatPayService::class);
        $mock->shouldReceive('isConfigured')->andReturn(false);
        // refund() should NOT be called
        $this->app->instance(WechatPayService::class, $mock);

        $service = app(RefundService::class);
        $result = $service->initiateProviderRefund($refund['refund_id']);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('未配置', $result['message']);

        // Refund stays PROCESSING (not FAILED, because we didn't even call provider)
        $dbRefund = DB::table('order_refunds')->where('id', $refund['refund_id'])->first();
        $this->assertEquals(RefundStatus::PROCESSING, $dbRefund->status);
    }

    // ============================================================
    // Test 12: no database transaction wraps provider call
    // ============================================================
    public function test_no_database_transaction_wraps_provider_call()
    {
        // Verify the source code structure: provider call is outside DB::transaction
        $source = file_get_contents(app_path('Modules/Refund/Services/RefundService.php'));

        // The provider call must NOT be inside DB::transaction
        // Find initiateProviderRefund method
        $methodStart = strpos($source, 'public function initiateProviderRefund');
        $methodEnd = strpos($source, 'private function mapPayTypeToProvider', $methodStart);
        $method = substr($source, $methodStart, $methodEnd - $methodStart);

        // Provider call line
        $this->assertStringContainsString('$provider->refund(', $method);

        // The provider call must come BEFORE the DB::transaction for persistence
        $providerCallPos = strpos($method, '$provider->refund(');
        $persistTransactionPos = strpos($method, 'DB::transaction(function () use ($refundId, $result');

        $this->assertNotFalse($providerCallPos);
        $this->assertNotFalse($persistTransactionPos);
        $this->assertLessThan($persistTransactionPos, $providerCallPos,
            'Provider API call must happen BEFORE the persistence DB transaction');
    }
}
