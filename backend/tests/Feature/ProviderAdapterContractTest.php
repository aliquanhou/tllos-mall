<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Modules\Refund\Contracts\RefundProviderInterface;
use App\Modules\Refund\Contracts\RefundProviderResult;
use App\Modules\Refund\Adapters\AlipayRefundAdapter;
use App\Modules\Refund\Adapters\WechatRefundAdapter;
use App\Modules\Refund\Adapters\RefundProviderFactory;
use App\Modules\Payment\Services\AlipayService;
use App\Modules\Payment\Services\WechatPayService;
use Mockery;

/**
 * P1-PI-01: Provider Adapter Contract Tests
 *
 * Verifies the unified RefundProviderInterface contract:
 * - All adapters implement the interface
 * - RefundProviderResult has correct status semantics
 * - Factory resolves correct adapter
 * - Production + unconfigured = FAIL CLOSED (not mock)
 * - Network timeout → UNKNOWN (not FAILED)
 */
class ProviderAdapterContractTest extends TestCase
{
    // ============================================================
    // RefundProviderResult DTO Tests
    // ============================================================

    public function test_result_success_status()
    {
        $result = RefundProviderResult::success('REF001', 'TXN001', '50.00', ['raw' => 'data']);
        $this->assertTrue($result->success);
        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isProcessing());
        $this->assertFalse($result->isFailed());
        $this->assertFalse($result->isUnknown());
        $this->assertEquals('REF001', $result->providerRefundNo);
        $this->assertEquals('TXN001', $result->providerTransactionNo);
        $this->assertEquals('50.00', $result->amount);
    }

    public function test_result_processing_status()
    {
        $result = RefundProviderResult::processing('REF002', 'TXN002', '30.00');
        $this->assertTrue($result->success);
        $this->assertTrue($result->isProcessing());
        $this->assertFalse($result->isSuccess());
        $this->assertEquals(RefundProviderResult::STATUS_PROCESSING, $result->status);
    }

    public function test_result_failed_status()
    {
        $result = RefundProviderResult::failed('refund rejected');
        $this->assertFalse($result->success);
        $this->assertTrue($result->isFailed());
        $this->assertEquals('refund rejected', $result->message);
    }

    public function test_result_unknown_status()
    {
        $result = RefundProviderResult::unknown('timeout');
        $this->assertFalse($result->success);
        $this->assertTrue($result->isUnknown());
        $this->assertFalse($result->isFailed());
        $this->assertEquals('timeout', $result->message);
    }

    // ============================================================
    // Factory Tests
    // ============================================================

    public function test_factory_resolves_alipay_adapter()
    {
        $factory = app(RefundProviderFactory::class);
        $provider = $factory->make('alipay');
        $this->assertNotNull($provider);
        $this->assertInstanceOf(AlipayRefundAdapter::class, $provider);
        $this->assertInstanceOf(RefundProviderInterface::class, $provider);
        $this->assertEquals('alipay', $provider->getProviderName());
    }

    public function test_factory_resolves_wechat_adapter()
    {
        $factory = app(RefundProviderFactory::class);
        $provider = $factory->make('wechat');
        $this->assertNotNull($provider);
        $this->assertInstanceOf(WechatRefundAdapter::class, $provider);
        $this->assertInstanceOf(RefundProviderInterface::class, $provider);
        $this->assertEquals('wechat', $provider->getProviderName());
    }

    public function test_factory_returns_null_for_unknown_provider()
    {
        $factory = app(RefundProviderFactory::class);
        $this->assertNull($factory->make('paypal'));
        $this->assertNull($factory->make('unknown'));
    }

    public function test_factory_has_and_available_providers()
    {
        $factory = app(RefundProviderFactory::class);
        $this->assertTrue($factory->has('alipay'));
        $this->assertTrue($factory->has('wechat'));
        $this->assertFalse($factory->has('paypal'));
        $this->assertContains('alipay', $factory->getAvailableProviders());
        $this->assertContains('wechat', $factory->getAvailableProviders());
    }

    // ============================================================
    // Alipay Adapter Tests (with mocked AlipayService)
    // ============================================================

    public function test_alipay_adapter_returns_success_on_sync_refund()
    {
        $mockAlipay = Mockery::mock(AlipayService::class);
        $mockAlipay->shouldReceive('isConfigured')->andReturn(true);
        $mockAlipay->shouldReceive('refund')->once()->andReturn([
            'success' => true,
            'refund_id' => '202100619764856820260911001',
            'out_refund_no' => 'RF20260911001',
        ]);

        $adapter = new AlipayRefundAdapter($mockAlipay);
        $result = $adapter->refund([
            'out_trade_no' => 'PAY20260911001',
            'out_request_no' => 'RF20260911001',
            'amount' => '50.00',
            'reason' => 'test refund',
        ]);

        $this->assertTrue($result->isSuccess());
        // CRITICAL: Alipay has no independent Provider Refund ID.
        // providerRefundNo must be empty (NULL), NOT out_request_no.
        // out_request_no is merchant identity, must not be forged as provider identity.
        $this->assertEmpty($result->providerRefundNo, 'Alipay must NOT forge provider_refund_no from out_request_no');
        $this->assertEquals('50.00', $result->amount);
        $this->assertEquals('202100619764856820260911001', $result->providerTransactionNo);
    }

    public function test_alipay_adapter_returns_failed_on_provider_error()
    {
        $mockAlipay = Mockery::mock(AlipayService::class);
        $mockAlipay->shouldReceive('isConfigured')->andReturn(true);
        $mockAlipay->shouldReceive('refund')->once()->andReturn([
            'success' => false,
            'message' => 'ACQ.TRADE_NOT_EXIST',
        ]);

        $adapter = new AlipayRefundAdapter($mockAlipay);
        $result = $adapter->refund([
            'out_trade_no' => 'PAY001',
            'out_request_no' => 'RF001',
            'amount' => '50.00',
            'reason' => 'test',
        ]);

        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('ACQ.TRADE_NOT_EXIST', $result->message);
    }

    public function test_alipay_adapter_timeout_returns_unknown_not_failed()
    {
        $mockAlipay = Mockery::mock(AlipayService::class);
        $mockAlipay->shouldReceive('isConfigured')->andReturn(true);
        $mockAlipay->shouldReceive('refund')->once()->andThrow(new \RuntimeException('Connection timed out after 30000ms'));

        $adapter = new AlipayRefundAdapter($mockAlipay);
        $result = $adapter->refund([
            'out_trade_no' => 'PAY001',
            'out_request_no' => 'RF001',
            'amount' => '50.00',
            'reason' => 'test',
        ]);

        // CRITICAL: timeout must be UNKNOWN, not FAILED
        $this->assertTrue($result->isUnknown());
        $this->assertFalse($result->isFailed());
        $this->assertStringContainsString('异常', $result->message);
    }

    // ============================================================
    // WeChat Adapter Tests (with mocked WechatPayService)
    // ============================================================

    public function test_wechat_adapter_unconfigured_fails_closed()
    {
        $mockWechat = Mockery::mock(WechatPayService::class);
        $mockWechat->shouldReceive('isConfigured')->andReturn(false);

        $adapter = new WechatRefundAdapter($mockWechat);
        $result = $adapter->refund([
            'out_trade_no' => 'PAY001',
            'out_request_no' => 'RF001',
            'amount' => '50.00',
            'reason' => 'test',
        ]);

        // Production + unconfigured = FAIL CLOSED, never mock success
        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('未配置', $result->message);
    }

    public function test_wechat_adapter_returns_processing_on_async_accept()
    {
        $mockWechat = Mockery::mock(WechatPayService::class);
        $mockWechat->shouldReceive('isConfigured')->andReturn(true);
        $mockWechat->shouldReceive('refund')->once()->andReturn([
            'success' => true,
            'refund_id' => '503018000100202609110001',
            'out_refund_no' => 'RF20260911001',
            'transaction_id' => '4200001234202609110001',
        ]);

        $adapter = new WechatRefundAdapter($mockWechat);
        $result = $adapter->refund([
            'out_trade_no' => 'PAY20260911001',
            'out_request_no' => 'RF20260911001',
            'amount' => '50.00',
            'reason' => 'test',
            'notify_url' => 'https://mall.tllos.com/api/v1/payment/refund-notify/wechat',
        ]);

        // WeChat is async: HTTP 200 = PROCESSING, not SUCCESS
        $this->assertTrue($result->isProcessing());
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('503018000100202609110001', $result->providerRefundNo);
    }

    public function test_wechat_adapter_timeout_returns_unknown()
    {
        $mockWechat = Mockery::mock(WechatPayService::class);
        $mockWechat->shouldReceive('isConfigured')->andReturn(true);
        $mockWechat->shouldReceive('refund')->once()->andThrow(new \RuntimeException('cURL error 28: timeout'));

        $adapter = new WechatRefundAdapter($mockWechat);
        $result = $adapter->refund([
            'out_trade_no' => 'PAY001',
            'out_request_no' => 'RF001',
            'amount' => '50.00',
            'reason' => 'test',
        ]);

        $this->assertTrue($result->isUnknown());
        $this->assertFalse($result->isFailed());
    }

    // ============================================================
    // Provider Identity Semantics Tests (PI-01-R1 CORE)
    // ============================================================

    public function test_alipay_success_does_not_forge_provider_refund_no()
    {
        $mockAlipay = Mockery::mock(AlipayService::class);
        $mockAlipay->shouldReceive('isConfigured')->andReturn(true);
        $mockAlipay->shouldReceive('refund')->once()->andReturn([
            'success' => true,
            'refund_id' => '202100619764856820260911001',
            'out_refund_no' => 'RF20260911001',
        ]);

        $adapter = new AlipayRefundAdapter($mockAlipay);
        $result = $adapter->refund([
            'out_trade_no' => 'PAY001',
            'out_request_no' => 'RF20260911001',
            'amount' => '50.00',
            'reason' => 'test',
        ]);

        // Alipay has NO independent Provider Refund ID.
        // providerRefundNo must be empty, even though out_request_no = RF20260911001.
        $this->assertTrue($result->isSuccess());
        $this->assertEmpty($result->providerRefundNo,
            'Alipay provider_refund_no must be NULL, not out_request_no');
        // providerTransactionNo = trade_no (Alipay transaction ID) — this IS provider identity
        $this->assertEquals('202100619764856820260911001', $result->providerTransactionNo);
    }

    public function test_wechat_processing_saves_refund_id_as_provider_identity()
    {
        $mockWechat = Mockery::mock(WechatPayService::class);
        $mockWechat->shouldReceive('isConfigured')->andReturn(true);
        $mockWechat->shouldReceive('refund')->once()->andReturn([
            'success' => true,
            'refund_id' => '503018000100202609110001',
            'out_refund_no' => 'RF20260911001',
            // Note: WeChat refund response does NOT include transaction_id
        ]);

        $adapter = new WechatRefundAdapter($mockWechat);
        $result = $adapter->refund([
            'out_trade_no' => 'PAY001',
            'out_request_no' => 'RF20260911001',
            'amount' => '50.00',
            'payment_amount' => '100.00',
            'provider_transaction_no' => '4200001234202609110001',
            'reason' => 'test',
            'notify_url' => 'https://mall.tllos.com/callback',
        ]);

        // WeChat HAS independent refund_id — this IS provider identity
        $this->assertTrue($result->isProcessing());
        $this->assertEquals('503018000100202609110001', $result->providerRefundNo,
            'WeChat provider_refund_no must be refund_id');
        $this->assertEquals('4200001234202609110001', $result->providerTransactionNo);
    }

    public function test_alipay_timeout_does_not_forge_any_identity()
    {
        $mockAlipay = Mockery::mock(AlipayService::class);
        $mockAlipay->shouldReceive('isConfigured')->andReturn(true);
        $mockAlipay->shouldReceive('refund')->once()->andThrow(new \RuntimeException('timeout'));

        $adapter = new AlipayRefundAdapter($mockAlipay);
        $result = $adapter->refund([
            'out_trade_no' => 'PAY001',
            'out_request_no' => 'RF001',
            'amount' => '50.00',
            'reason' => 'test',
        ]);

        // Timeout = UNKNOWN. No provider identity can be forged.
        $this->assertTrue($result->isUnknown());
        $this->assertEmpty($result->providerRefundNo, 'timeout must not forge provider_refund_no');
        $this->assertEmpty($result->providerTransactionNo, 'timeout must not forge provider_transaction_no');
    }

    public function test_wechat_timeout_does_not_forge_any_identity()
    {
        $mockWechat = Mockery::mock(WechatPayService::class);
        $mockWechat->shouldReceive('isConfigured')->andReturn(true);
        $mockWechat->shouldReceive('refund')->once()->andThrow(new \RuntimeException('timeout'));

        $adapter = new WechatRefundAdapter($mockWechat);
        $result = $adapter->refund([
            'out_trade_no' => 'PAY001',
            'out_request_no' => 'RF001',
            'amount' => '50.00',
            'reason' => 'test',
        ]);

        $this->assertTrue($result->isUnknown());
        $this->assertEmpty($result->providerRefundNo, 'timeout must not forge provider_refund_no');
        $this->assertEmpty($result->providerTransactionNo, 'timeout must not forge provider_transaction_no');
    }

    public function test_identity_separation_merchant_vs_provider()
    {
        // Verify the conceptual separation:
        //   merchant identity = refund_no (we generate, sent as out_request_no/out_refund_no)
        //   provider identity = provider_refund_no (provider generates, or NULL)
        //   provider transaction = provider_transaction_no (original payment transaction)

        // Alipay: merchant identity exists, provider refund identity = NULL
        $mockAlipay = Mockery::mock(AlipayService::class);
        $mockAlipay->shouldReceive('isConfigured')->andReturn(true);
        $mockAlipay->shouldReceive('refund')->once()->andReturn([
            'success' => true,
            'refund_id' => 'TXN_ALIPAY_001',
            'out_refund_no' => 'RF_MERCHANT_001',
        ]);
        $alipayAdapter = new AlipayRefundAdapter($mockAlipay);
        $alipayResult = $alipayAdapter->refund([
            'out_trade_no' => 'PAY001',
            'out_request_no' => 'RF_MERCHANT_001',
            'amount' => '50.00',
            'reason' => 'test',
        ]);

        // merchant identity (RF_MERCHANT_001) is sent TO provider but NOT stored as provider identity
        $this->assertEmpty($alipayResult->providerRefundNo);
        $this->assertEquals('TXN_ALIPAY_001', $alipayResult->providerTransactionNo);

        // WeChat: merchant identity exists, provider refund identity = refund_id
        $mockWechat = Mockery::mock(WechatPayService::class);
        $mockWechat->shouldReceive('isConfigured')->andReturn(true);
        $mockWechat->shouldReceive('refund')->once()->andReturn([
            'success' => true,
            'refund_id' => 'REFUND_WECHAT_001',
            'out_refund_no' => 'RF_MERCHANT_002',
            // Note: WeChat refund response does NOT include transaction_id
        ]);
        $wechatAdapter = new WechatRefundAdapter($mockWechat);
        $wechatResult = $wechatAdapter->refund([
            'out_trade_no' => 'PAY002',
            'out_request_no' => 'RF_MERCHANT_002',
            'amount' => '50.00',
            'payment_amount' => '100.00',
            'provider_transaction_no' => 'TXN_WECHAT_001',
            'reason' => 'test',
            'notify_url' => 'https://mall.tllos.com/callback',
        ]);

        // provider identity = WeChat refund_id (different from merchant identity)
        $this->assertEquals('REFUND_WECHAT_001', $wechatResult->providerRefundNo);
        $this->assertNotEquals('RF_MERCHANT_002', $wechatResult->providerRefundNo);
        $this->assertEquals('TXN_WECHAT_001', $wechatResult->providerTransactionNo);
    }

    // ============================================================
    // Interface Contract Tests
    // ============================================================

    public function test_all_adapters_implement_required_methods()
    {
        $reflection = new \ReflectionClass(RefundProviderInterface::class);
        $methods = array_map(fn($m) => $m->getName(), $reflection->getMethods());

        $required = ['refund', 'query', 'verifyNotify', 'parseNotify', 'getProviderName', 'isConfigured'];
        foreach ($required as $method) {
            $this->assertContains($method, $methods, "Interface missing method: $method");
        }
    }

    public function test_alipay_query_returns_unknown_not_implemented()
    {
        $mockAlipay = Mockery::mock(AlipayService::class);
        $adapter = new AlipayRefundAdapter($mockAlipay);
        $result = $adapter->query('RF001', 'PAY001');
        // Query not yet implemented (P1-PI-05), should return UNKNOWN
        $this->assertTrue($result->isUnknown());
    }

    public function test_wechat_query_returns_unknown_not_implemented()
    {
        $mockWechat = Mockery::mock(WechatPayService::class);
        $adapter = new WechatRefundAdapter($mockWechat);
        $result = $adapter->query('RF001', 'PAY001');
        $this->assertTrue($result->isUnknown());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
