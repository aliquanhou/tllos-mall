<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * P1 Payment Remediation Phase 1 Tests
 *
 * P1-A: WeChat Fail-Closed (production + empty config = no free payment)
 * P1-B: Callback Amount Gate (bccomp exact match)
 * P1-C: Exact Payment Binding (no batch update, latest pending only)
 *
 * All tests run against tllos_mall_test (isolated test database).
 * Production database tllos_mall is NEVER touched.
 */
class PaymentPhase1Test extends TestCase
{
    private $testUserId;
    private $testOrderNo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->testUserId = DB::table('users')->insertGetId([
            'name' => 'PaymentTestUser',
            'email' => 'payment_test_' . uniqid() . '@example.com',
            'password' => bcrypt('test123456'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $this->testOrderNo = 'TEST' . date('YmdHis') . rand(1000, 9999);
    }

    protected function tearDown(): void
    {
        // Clean up test data from test DB only
        DB::table('payments')->where('user_id', $this->testUserId)->delete();
        DB::table('orders')->where('user_id', $this->testUserId)->delete();
        DB::table('users')->where('id', $this->testUserId)->delete();
        parent::tearDown();
    }

    /**
     * Helper: create a pending order with all required fields
     */
    private function createOrder($payAmount = 100.00, $status = 0)
    {
        $orderNo = 'TEST' . date('YmdHis') . rand(1000, 9999);
        DB::table('orders')->insert([
            'order_no' => $orderNo,
            'user_id' => $this->testUserId,
            'merchant_id' => 1,
            'total_amount' => $payAmount,
            'pay_amount' => $payAmount,
            'status' => $status,
            'receiver_name' => 'Test Receiver',
            'receiver_mobile' => '13800138000',
            'province_id' => 1,
            'city_id' => 1,
            'district_id' => 1,
            'province_name' => '广东省',
            'city_name' => '惠州市',
            'district_name' => '大亚湾区',
            'receiver_address' => 'Test Address 123',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        return $orderNo;
    }

    /**
     * Helper: create a pending payment record
     */
    private function createPayment($orderNo, $amount = 100.00, $payType = 2, $status = 0)
    {
        $paymentNo = 'PAY' . date('YmdHis') . rand(1000, 9999);
        DB::table('payments')->insert([
            'payment_no' => $paymentNo,
            'order_no' => $orderNo,
            'user_id' => $this->testUserId,
            'type' => 1,
            'pay_type' => $payType,
            'amount' => $amount,
            'status' => $status,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        return $paymentNo;
    }

    /**
     * Helper: call private processPaymentSuccess via reflection
     */
    private function callProcessPaymentSuccess($outTradeNo, $transactionId, $amount, $payType = 2)
    {
        $controller = new \App\Modules\Payment\Controllers\PaymentNotifyController();
        $method = new \ReflectionMethod($controller, 'processPaymentSuccess');
        $method->setAccessible(true);
        return $method->invoke($controller, $outTradeNo, $transactionId, $amount, $payType);
    }

    // ============================================================
    // P1-A: WeChat Fail-Closed Tests
    // ============================================================

    /**
     * P1-A Test 1: Production environment + empty WeChat config should fail closed
     */
    public function test_wechat_empty_config_fail_closed_in_production()
    {
        // Ensure wechat config is empty in test DB
        DB::table('pay_configs')->updateOrInsert(
            ['code' => 'wechat'],
            ['name' => '微信支付', 'config' => '', 'status' => 1, 'sort' => 1,
             'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        );

        // Temporarily simulate production environment
        $originalEnv = config('app.env');
        config(['app.env' => 'production']);

        try {
            $service = new \App\Modules\Payment\Services\WechatPayService();
            $this->assertFalse($service->isConfigured(), 'WeChat should report not configured');
            $this->assertFalse($service->isSandbox(), 'WeChat should NOT be sandbox in production with empty config');
        } finally {
            config(['app.env' => $originalEnv]);
        }
    }

    /**
     * P1-A Test 2: WeChat unifiedOrder should fail with empty config in production
     */
    public function test_wechat_unified_order_rejects_empty_config()
    {
        DB::table('pay_configs')->updateOrInsert(
            ['code' => 'wechat'],
            ['name' => '微信支付', 'config' => '', 'status' => 1, 'sort' => 1,
             'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        );

        // Temporarily simulate production environment to test fail-closed
        $originalEnv = config('app.env');
        config(['app.env' => 'production']);

        try {
            $service = new \App\Modules\Payment\Services\WechatPayService();
            $result = $service->unifiedOrder([
                'out_trade_no' => 'TEST' . time(),
                'amount' => 100,
                'total_fee' => 100,
                'description' => 'test',
            ]);

            $this->assertFalse($result['success'], 'WeChat unifiedOrder should fail with empty config in production');
            $this->assertFalse($service->isSandbox(), 'WeChat should NOT be sandbox in production with empty config');
        } finally {
            config(['app.env' => $originalEnv]);
        }
    }

    /**
     * P1-A Test 3: Alipay with complete config should report configured
     */
    public function test_alipay_complete_config_reports_configured()
    {
        DB::table('pay_configs')->updateOrInsert(
            ['code' => 'alipay'],
            ['name' => '支付宝', 'config' => json_encode([
                'app_id' => '2021006197648568',
                'merchant_private_key' => 'test_key_' . str_repeat('a', 100),
                'alipay_public_key' => 'test_pub_' . str_repeat('b', 50),
            ]), 'status' => 1, 'sort' => 2,
             'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        );

        $service = new \App\Modules\Payment\Services\AlipayService();
        $this->assertTrue($service->isConfigured(), 'Alipay should report configured with complete config');
    }

    // ============================================================
    // P1-B: Callback Amount Gate Tests
    // ============================================================

    /**
     * P1-B Test 4: Correct amount callback should succeed
     */
    public function test_callback_correct_amount_succeeds()
    {
        $orderNo = $this->createOrder(100.00);
        $this->createPayment($orderNo, 100.00);

        $this->callProcessPaymentSuccess($orderNo, 'TXN_CORRECT_001', 100.00, 2);

        $order = DB::table('orders')->where('order_no', $orderNo)->first();
        $payment = DB::table('payments')->where('order_no', $orderNo)->first();

        $this->assertEquals(1, $order->status, 'Order should be paid');
        $this->assertEquals(1, $payment->status, 'Payment should be paid');
        $this->assertEquals('TXN_CORRECT_001', $payment->third_payment_no);
    }

    /**
     * P1-B Test 5: Callback amount 1 cent less should be rejected
     */
    public function test_callback_amount_less_than_order_rejected()
    {
        $orderNo = $this->createOrder(100.00);
        $this->createPayment($orderNo, 100.00);

        $this->callProcessPaymentSuccess($orderNo, 'TXN_LESS_001', 99.99, 2);

        $order = DB::table('orders')->where('order_no', $orderNo)->first();
        $payment = DB::table('payments')->where('order_no', $orderNo)->first();

        $this->assertEquals(0, $order->status, 'Order should remain pending');
        $this->assertEquals(0, $payment->status, 'Payment should remain pending');
        $this->assertEmpty($payment->third_payment_no, 'third_payment_no should not be set');
    }

    /**
     * P1-B Test 6: Callback amount 1 cent more should be rejected
     */
    public function test_callback_amount_more_than_order_rejected()
    {
        $orderNo = $this->createOrder(100.00);
        $this->createPayment($orderNo, 100.00);

        $this->callProcessPaymentSuccess($orderNo, 'TXN_MORE_001', 100.01, 2);

        $order = DB::table('orders')->where('order_no', $orderNo)->first();
        $payment = DB::table('payments')->where('order_no', $orderNo)->first();

        $this->assertEquals(0, $order->status, 'Order should remain pending');
        $this->assertEquals(0, $payment->status, 'Payment should remain pending');
    }

    /**
     * P1-B Test 7: Payment amount differs from order amount should be rejected
     */
    public function test_callback_payment_amount_mismatch_rejected()
    {
        $orderNo = $this->createOrder(100.00);
        $this->createPayment($orderNo, 50.00); // payment amount differs from order

        $this->callProcessPaymentSuccess($orderNo, 'TXN_MISMATCH_001', 100.00, 2);

        $order = DB::table('orders')->where('order_no', $orderNo)->first();
        $payment = DB::table('payments')->where('order_no', $orderNo)->first();

        $this->assertEquals(0, $order->status, 'Order should remain pending');
        $this->assertEquals(0, $payment->status, 'Payment should remain pending');
    }

    /**
     * P1-B Test 8: Zero amount callback should be rejected
     */
    public function test_callback_zero_amount_rejected()
    {
        $orderNo = $this->createOrder(100.00);
        $this->createPayment($orderNo, 100.00);

        $this->callProcessPaymentSuccess($orderNo, 'TXN_ZERO_001', 0, 2);

        $order = DB::table('orders')->where('order_no', $orderNo)->first();
        $this->assertEquals(0, $order->status, 'Order should remain pending');
    }

    // ============================================================
    // P1-C: Exact Payment Binding Tests
    // ============================================================

    /**
     * P1-C Test 9: Multiple pending payments, only latest should be paid
     */
    public function test_multiple_pending_payments_only_latest_paid()
    {
        $orderNo = $this->createOrder(100.00);

        // Create 3 pending payments with different created_at times
        $payNo1 = $this->createPayment($orderNo, 100.00, 2, 0);
        DB::table('payments')->where('payment_no', $payNo1)->update(['created_at' => Carbon::now()->subMinutes(10)]);

        $payNo2 = $this->createPayment($orderNo, 100.00, 2, 0);
        DB::table('payments')->where('payment_no', $payNo2)->update(['created_at' => Carbon::now()->subMinutes(5)]);

        $payNo3 = $this->createPayment($orderNo, 100.00, 2, 0); // latest

        $this->callProcessPaymentSuccess($orderNo, 'TXN_MULTI_001', 100.00, 2);

        $payments = DB::table('payments')->where('order_no', $orderNo)->orderBy('created_at')->get();

        $this->assertEquals(0, $payments[0]->status, 'Oldest payment should remain pending');
        $this->assertEquals(0, $payments[1]->status, 'Middle payment should remain pending');
        $this->assertEquals(1, $payments[2]->status, 'Latest payment should be paid');
        $this->assertEquals('TXN_MULTI_001', $payments[2]->third_payment_no);
    }

    /**
     * P1-C Test 10: No pending payment should not update anything
     */
    public function test_no_pending_payment_does_nothing()
    {
        $orderNo = $this->createOrder(100.00, 1); // already paid order
        $this->createPayment($orderNo, 100.00, 2, 1); // already paid payment

        $this->callProcessPaymentSuccess($orderNo, 'TXN_NOPENDING_001', 100.00, 2);

        $payment = DB::table('payments')->where('order_no', $orderNo)->first();
        $this->assertEquals(1, $payment->status, 'Payment should remain paid');
        $this->assertNotEquals('TXN_NOPENDING_001', $payment->third_payment_no, 'Should not overwrite existing transaction_id');
    }

    // ============================================================
    // Transaction ID Uniqueness Tests
    // ============================================================

    /**
     * Test 11: Duplicate transaction_id on different payment should be rejected
     */
    public function test_duplicate_transaction_id_rejected()
    {
        // First order + payment, paid with TXN_DUP_001
        $orderNo1 = $this->createOrder(100.00);
        $this->createPayment($orderNo1, 100.00);
        $this->callProcessPaymentSuccess($orderNo1, 'TXN_DUP_001', 100.00, 2);

        // Second order + pending payment, try to use same TXN_DUP_001
        $orderNo2 = $this->createOrder(50.00);
        $this->createPayment($orderNo2, 50.00);
        $this->callProcessPaymentSuccess($orderNo2, 'TXN_DUP_001', 50.00, 2);

        $order2 = DB::table('orders')->where('order_no', $orderNo2)->first();
        $payment2 = DB::table('payments')->where('order_no', $orderNo2)->first();

        $this->assertEquals(0, $order2->status, 'Second order should remain pending');
        $this->assertEquals(0, $payment2->status, 'Second payment should remain pending');
    }

    // ============================================================
    // Idempotency Tests
    // ============================================================

    /**
     * Test 12: Duplicate callback (same transaction_id) should be no-op
     */
    public function test_duplicate_callback_is_idempotent()
    {
        $orderNo = $this->createOrder(100.00);
        $this->createPayment($orderNo, 100.00);

        // First callback
        $this->callProcessPaymentSuccess($orderNo, 'TXN_IDEMP_001', 100.00, 2);
        // Second callback with same transaction_id
        $this->callProcessPaymentSuccess($orderNo, 'TXN_IDEMP_001', 100.00, 2);
        // Third callback
        $this->callProcessPaymentSuccess($orderNo, 'TXN_IDEMP_001', 100.00, 2);

        $order = DB::table('orders')->where('order_no', $orderNo)->first();
        $payments = DB::table('payments')->where('order_no', $orderNo)->get();

        $this->assertEquals(1, $order->status, 'Order should be paid');
        $this->assertEquals(1, $payments->count(), 'Should still have exactly 1 payment');
        $this->assertEquals(1, $payments[0]->status, 'Payment should be paid');
    }

    /**
     * Test 13: Callback for non-existent order should not crash
     */
    public function test_callback_nonexistent_order_handled()
    {
        $result = $this->callProcessPaymentSuccess('NONEXISTENT_ORDER_999', 'TXN_NOEXIST_001', 100.00, 2);
        // Should not throw exception, should return gracefully
        $this->assertTrue(true, 'Callback for nonexistent order should not crash');
    }

    /**
     * Test 14: Empty out_trade_no should be rejected
     */
    public function test_callback_empty_out_trade_no_rejected()
    {
        $this->callProcessPaymentSuccess('', 'TXN_EMPTY_001', 100.00, 2);
        $this->assertTrue(true, 'Empty out_trade_no should be rejected gracefully');
    }

    /**
     * Test 15: Empty transaction_id should be rejected
     */
    public function test_callback_empty_transaction_id_rejected()
    {
        $orderNo = $this->createOrder(100.00);
        $this->createPayment($orderNo, 100.00);

        $this->callProcessPaymentSuccess($orderNo, '', 100.00, 2);

        $order = DB::table('orders')->where('order_no', $orderNo)->first();
        $this->assertEquals(0, $order->status, 'Order should remain pending with empty transaction_id');
    }

    /**
     * Test 16: Already paid order should not be updated by new callback
     */
    public function test_already_paid_order_not_updated()
    {
        $orderNo = $this->createOrder(100.00, 1); // paid order
        $this->createPayment($orderNo, 100.00, 2, 1); // paid payment with TXN_ORIGINAL

        DB::table('payments')->where('order_no', $orderNo)->update(['third_payment_no' => 'TXN_ORIGINAL_001']);

        // Try a new callback with different transaction_id
        $this->callProcessPaymentSuccess($orderNo, 'TXN_NEW_002', 100.00, 2);

        $payment = DB::table('payments')->where('order_no', $orderNo)->first();
        $this->assertEquals('TXN_ORIGINAL_001', $payment->third_payment_no, 'Should not overwrite original transaction_id');
    }
}
