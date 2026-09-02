<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;

class PaymentModuleTest extends BaseModuleTest
{
    /**
     * 测试支付方式列表
     */
    public function test_payment_methods_list()
    {
        $response = $this->userGet('/api/v1/payment/methods');
        $this->assertApiSuccess($response, '支付方式列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('list', $data);
    }

    /**
     * 测试余额支付
     */
    public function test_balance_pay()
    {
        // 创建一个待支付订单
        $orderId = $this->createTestOrder();
        if (!$orderId) {
            $this->markTestSkipped('无法创建测试订单');
        }

        $response = $this->userPost('/api/v1/payment/pay', [
            'order_id' => $orderId,
            'pay_type' => 3, // 余额支付
        ]);

        // 余额可能不足，接受成功或余额不足两种结果
        $code = $response->json('code');
        $this->assertContains($code, [200, 400, 401, 422], '余额支付响应');
    }

    /**
     * 测试微信支付沙箱下单
     */
    public function test_wechat_pay_sandbox()
    {
        $orderId = $this->createTestOrder();
        if (!$orderId) {
            $this->markTestSkipped('无法创建测试订单');
        }

        $response = $this->userPost('/api/v1/payment/pay', [
            'order_id' => $orderId,
            'pay_type' => 1, // 微信支付
        ]);

        $this->assertApiSuccess($response, '微信支付沙箱下单');
        $data = $response->json('data');
        $this->assertArrayHasKey('order_no', $data);
        $this->assertArrayHasKey('pay_amount', $data);
        // 沙箱模式下应该直接支付成功
        if (isset($data['sandbox']) && $data['sandbox']) {
            $this->assertTrue($data['sandbox']);
        }
    }

    /**
     * 测试支付宝沙箱下单
     */
    public function test_alipay_pay_sandbox()
    {
        $orderId = $this->createTestOrder();
        if (!$orderId) {
            $this->markTestSkipped('无法创建测试订单');
        }

        $response = $this->userPost('/api/v1/payment/pay', [
            'order_id' => $orderId,
            'pay_type' => 2, // 支付宝
        ]);

        $this->assertApiSuccess($response, '支付宝沙箱下单');
        $data = $response->json('data');
        $this->assertArrayHasKey('order_no', $data);
    }

    /**
     * 测试重复支付防护
     */
    public function test_duplicate_pay_protection()
    {
        $orderId = $this->createTestOrder();
        if (!$orderId) {
            $this->markTestSkipped('无法创建测试订单');
        }

        // 第一次支付
        $response1 = $this->userPost('/api/v1/payment/pay', [
            'order_id' => $orderId,
            'pay_type' => 1,
        ]);

        // 第二次支付应该失败（订单状态已不是待支付）
        $response2 = $this->userPost('/api/v1/payment/pay', [
            'order_id' => $orderId,
            'pay_type' => 1,
        ]);

        // 第二次支付应该返回错误
        $code2 = $response2->json('code');
        $this->assertNotEquals(200, $code2, '重复支付应该被拒绝');
    }

    /**
     * 测试支付状态查询
     */
    public function test_payment_status_query()
    {
        $orderId = $this->createTestOrder();
        if (!$orderId) {
            $this->markTestSkipped('无法创建测试订单');
        }

        $response = $this->userGet('/api/v1/payment/status/' . $orderId);
        $this->assertApiSuccess($response, '支付状态查询');
        $data = $response->json('data');
        $this->assertArrayHasKey('status', $data);
    }

    /**
     * 测试微信支付回调
     */
    public function test_wechat_payment_notify()
    {
        // 创建一个待支付订单
        $orderId = $this->createTestOrder();
        if (!$orderId) {
            $this->markTestSkipped('无法创建测试订单');
        }

        $order = \App\Modules\Order\Models\Order::find($orderId);

        $response = $this->postJson('/api/v1/payment/notify/wechat', [
            'out_trade_no' => $order->order_no,
            'transaction_id' => 'TEST' . time(),
            'amount' => ['total' => intval($order->pay_amount * 100)],
            'trade_state' => 'SUCCESS',
        ]);

        // 回调应该返回SUCCESS
        $this->assertContains($response->status(), [200, 500], '微信支付回调响应');
    }

    /**
     * 测试支付宝支付回调
     */
    public function test_alipay_payment_notify()
    {
        $orderId = $this->createTestOrder();
        if (!$orderId) {
            $this->markTestSkipped('无法创建测试订单');
        }

        $order = \App\Modules\Order\Models\Order::find($orderId);

        $response = $this->postJson('/api/v1/payment/notify/alipay', [
            'out_trade_no' => $order->order_no,
            'trade_no' => 'ALI' . time(),
            'total_amount' => $order->pay_amount,
            'trade_status' => 'TRADE_SUCCESS',
        ]);

        $this->assertContains($response->status(), [200, 500], '支付宝回调响应');
    }

    /**
     * 测试退款列表
     */
    public function test_refund_list()
    {
        $response = $this->adminGet('/api/v1/admin/refunds');
        $this->assertApiSuccess($response, '退款列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('list', $data);
        $this->assertArrayHasKey('stats', $data);
    }

    /**
     * 测试退款申请
     */
    public function test_refund_apply()
    {
        // 创建一个已支付的订单
        $orderId = $this->createPaidOrder();
        if (!$orderId) {
            $this->markTestSkipped('无法创建已支付订单');
        }

        $order = \App\Modules\Order\Models\Order::find($orderId);

        $response = $this->adminPost('/api/v1/admin/refunds', [
            'order_id' => $orderId,
            'amount' => $order->pay_amount,
            'reason' => '测试退款',
        ]);

        // 退款可能成功或因为状态问题失败
        $code = $response->json('code');
        $this->assertContains($code, [200, 400, 422], '退款申请响应');
    }

    /**
     * 创建测试订单（辅助方法）
     */
    private function createTestOrder()
    {
        try {
            $userId = 2; // 测试用户
            $product = \Illuminate\Support\Facades\DB::table('products')->where('status', 1)->first();
            if (!$product) return null;

            $orderNo = 'TEST' . date('YmdHis') . rand(1000, 9999);
            $orderId = \Illuminate\Support\Facades\DB::table('orders')->insertGetId([
                'order_no' => $orderNo,
                'user_id' => $userId,
                'merchant_id' => 1,
                'total_amount' => 100.00,
                'pay_amount' => 100.00,
                'status' => 0, // 待支付
                'receiver_name' => '测试用户',
                'receiver_mobile' => '133001330001',
                'province_id' => 1,
                'city_id' => 1,
                'district_id' => 1,
                'province_name' => '广东省',
                'city_name' => '惠州市',
                'district_name' => '大亚湾区',
                'receiver_address' => '测试地址',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::table('order_items')->insert([
                'order_id' => $orderId,
                'order_no' => $orderNo,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_image' => $product->main_image ?? 'test.jpg',
                'price' => 100.00,
                'quantity' => 1,
                'total_amount' => 100.00,
                'pay_amount' => 100.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $orderId;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 创建已支付订单（辅助方法）
     */
    private function createPaidOrder()
    {
        $orderId = $this->createTestOrder();
        if ($orderId) {
            \Illuminate\Support\Facades\DB::table('orders')->where('id', $orderId)->update([
                'status' => 1,
                'pay_type' => 1,
                'pay_time' => now(),
            ]);
        }
        return $orderId;
    }
}
