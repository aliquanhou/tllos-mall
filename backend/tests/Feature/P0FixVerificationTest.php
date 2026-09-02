<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;
use Illuminate\Support\Facades\DB;

class P0FixVerificationTest extends BaseModuleTest
{
    // ========== P0-1: 分销佣金自动计算 ==========
    public function test_payment_creates_distribute_order_for_distribute_goods()
    {
        // 创建分销商
        $agentId = DB::table('distribute_agents')->insertGetId([
            'user_id' => 1, 'level_id' => 1, 'parent_id' => null,
            'real_name' => '佣金测试分销商', 'mobile' => '136' . time(),
            'status' => 1, 'total_income' => 0, 'available_income' => 0,
            'total_orders' => 0, 'total_members' => 0, 'apply_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // 创建分销商品（按比例5%）
        $productId = DB::table('products')->insertGetId([
            'merchant_id' => 0, 'category_id' => 1, 'brand_id' => 0,
            'name' => '佣金测试商品' . time(), 'main_image' => 'test.jpg',
            'price' => 100, 'stock' => 100, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('distribute_goods')->insert([
            'product_id' => $productId, 'product_name' => '佣金测试商品',
            'commission_type' => 1, 'commission_rate' => 5.00, 'commission_amount' => 0,
            'is_distribute' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // 创建订单（待付款，关联分销商）
        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'ORD' . time() . 'COMM', 'user_id' => 2, 'merchant_id' => 0,
            'agent_id' => $agentId, 'total_amount' => 100, 'pay_amount' => 100,
            'status' => 0, 'receiver_name' => '测试', 'receiver_mobile' => '13800138000',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => '广东', 'city_name' => '深圳', 'district_name' => '南山',
            'receiver_address' => '测试地址', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId, 'order_no' => 'ORD' . time() . 'COMM',
            'product_id' => $productId, 'product_name' => '佣金测试商品',
            'product_image' => 'test.jpg', 'price' => 100, 'quantity' => 1,
            'total_amount' => 100, 'pay_amount' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // 用户登录并支付
        $this->userLogin();
        $response = $this->userPost('/api/v1/payment/pay', [
            'order_id' => $orderId,
            'pay_type' => 1,
        ]);
        $this->assertApiSuccess($response, '订单支付');

        // 验证订单状态变为待发货
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(1, $order->status, '支付后订单状态应为1(待发货)');

        // 验证订单佣金字段
        $this->assertEquals(5.00, $order->commission, '100元商品5%佣金应为5元');

        // 验证分销订单记录已创建
        $distributeOrder = DB::table('distribute_orders')->where('order_id', $orderId)->first();
        $this->assertNotNull($distributeOrder, '支付成功后应创建分销订单记录');
        $this->assertEquals(5.00, $distributeOrder->commission, '分销订单佣金应为5元');
        $this->assertEquals($agentId, $distributeOrder->agent_id, '分销订单应关联分销商');
        $this->assertEquals(0, $distributeOrder->status, '分销订单状态应为0(待结算)');

        // 验证分销商累计佣金已更新
        $agent = DB::table('distribute_agents')->where('id', $agentId)->first();
        $this->assertEquals(5.00, $agent->total_income, '分销商累计佣金应增加5元');
    }

    public function test_payment_no_commission_for_non_distribute_goods()
    {
        // 创建普通商品（无分销配置）
        $productId = DB::table('products')->insertGetId([
            'merchant_id' => 0, 'category_id' => 1, 'brand_id' => 0,
            'name' => '普通商品' . time(), 'main_image' => 'test.jpg',
            'price' => 100, 'stock' => 100, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'ORD' . time() . 'NORM', 'user_id' => 2, 'merchant_id' => 0,
            'agent_id' => 0, 'total_amount' => 100, 'pay_amount' => 100,
            'status' => 0, 'receiver_name' => '测试', 'receiver_mobile' => '13800138001',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => '广东', 'city_name' => '深圳', 'district_name' => '南山',
            'receiver_address' => '测试地址', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId, 'order_no' => 'ORD' . time() . 'NORM',
            'product_id' => $productId, 'product_name' => '普通商品',
            'product_image' => 'test.jpg', 'price' => 100, 'quantity' => 1,
            'total_amount' => 100, 'pay_amount' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->userLogin();
        $this->userPost('/api/v1/payment/pay', ['order_id' => $orderId, 'pay_type' => 1]);

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(0, $order->commission, '普通商品佣金应为0');

        $distributeOrder = DB::table('distribute_orders')->where('order_id', $orderId)->first();
        $this->assertNull($distributeOrder, '普通商品不应创建分销订单');
    }

    public function test_payment_fixed_amount_commission()
    {
        // 创建分销商
        $agentId = DB::table('distribute_agents')->insertGetId([
            'user_id' => 1, 'level_id' => 1, 'parent_id' => null,
            'real_name' => '固定佣金分销商', 'mobile' => '135' . time(),
            'status' => 1, 'total_income' => 0, 'available_income' => 0,
            'total_orders' => 0, 'total_members' => 0, 'apply_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // 创建分销商品（固定金额10元）
        $productId = DB::table('products')->insertGetId([
            'merchant_id' => 0, 'category_id' => 1, 'brand_id' => 0,
            'name' => '固定佣金商品' . time(), 'main_image' => 'test.jpg',
            'price' => 200, 'stock' => 100, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('distribute_goods')->insert([
            'product_id' => $productId, 'product_name' => '固定佣金商品',
            'commission_type' => 2, 'commission_rate' => 0, 'commission_amount' => 10.00,
            'is_distribute' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'order_no' => 'ORD' . time() . 'FIXED', 'user_id' => 2, 'merchant_id' => 0,
            'agent_id' => $agentId, 'total_amount' => 200, 'pay_amount' => 200,
            'status' => 0, 'receiver_name' => '测试', 'receiver_mobile' => '13800138002',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => '广东', 'city_name' => '深圳', 'district_name' => '南山',
            'receiver_address' => '测试地址', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('order_items')->insert([
            'order_id' => $orderId, 'order_no' => 'ORD' . time() . 'FIXED',
            'product_id' => $productId, 'product_name' => '固定佣金商品',
            'product_image' => 'test.jpg', 'price' => 200, 'quantity' => 2,
            'total_amount' => 400, 'pay_amount' => 400,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->userLogin();
        $this->userPost('/api/v1/payment/pay', ['order_id' => $orderId, 'pay_type' => 1]);

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(20.00, $order->commission, '固定佣金10元x2件=20元');
    }

    // ========== P0-2: 商家结算余额更新 ==========
    public function test_settlement_confirm_updates_merchant_balance()
    {
        // 创建商家
        $merchantId = DB::table('merchants')->insertGetId([
            'user_id' => 1, 'username' => 'settletest' . time(),
            'password' => bcrypt('123456'), 'name' => '结算测试商家' . time(),
            'category_id' => 1, 'contact_name' => '测试', 'contact_mobile' => '134' . time(),
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'address' => '测试地址', 'status' => 1, 'balance' => 0,
            'frozen' => 0, 'total_income' => 0, 'deposit' => 0,
            'deposit_status' => 0, 'is_blacklisted' => 0, 'reject_count' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // 创建结算单
        $settlementId = DB::table('merchant_settlements')->insertGetId([
            'settlement_no' => 'STL' . time() . 'TEST',
            'merchant_id' => $merchantId,
            'order_amount' => 1000, 'order_count' => 10,
            'commission' => 50, 'refund_amount' => 0,
            'settlement_amount' => 950,
            'start_date' => now()->subDays(7), 'end_date' => now(),
            'status' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // 确认结算
        $response = $this->adminPost('/api/v1/admin/finance/settlement/' . $settlementId . '/confirm', []);
        $this->assertApiSuccess($response, '确认结算');

        // 验证结算单状态
        $settlement = DB::table('merchant_settlements')->where('id', $settlementId)->first();
        $this->assertEquals(1, $settlement->status, '结算单状态应为1(已结算)');
        $this->assertNotNull($settlement->settled_at, '结算时间应记录');

        // 验证商家余额已更新
        $merchant = DB::table('merchants')->where('id', $merchantId)->first();
        $this->assertEquals(950, $merchant->balance, '商家余额应增加950元');

        // 验证商家账户日志
        $log = DB::table('merchant_account_logs')->where('merchant_id', $merchantId)->first();
        $this->assertNotNull($log, '应记录商家账户日志');
        $this->assertEquals(950, $log->amount, '日志金额应为950');
        $this->assertEquals(0, $log->before_balance, '变动前余额应为0');
        $this->assertEquals(950, $log->after_balance, '变动后余额应为950');
    }

    public function test_settlement_confirm_cannot_repeat()
    {
        $merchantId = DB::table('merchants')->insertGetId([
            'user_id' => 1, 'username' => 'repeat' . time(),
            'password' => bcrypt('123456'), 'name' => '重复结算商家' . time(),
            'category_id' => 1, 'contact_name' => '测试', 'contact_mobile' => '133' . time(),
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'address' => '测试地址', 'status' => 1, 'balance' => 0,
            'frozen' => 0, 'total_income' => 0, 'deposit' => 0,
            'deposit_status' => 0, 'is_blacklisted' => 0, 'reject_count' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $settlementId = DB::table('merchant_settlements')->insertGetId([
            'settlement_no' => 'STL' . time() . 'REPEAT',
            'merchant_id' => $merchantId,
            'order_amount' => 500, 'order_count' => 5,
            'commission' => 25, 'refund_amount' => 0,
            'settlement_amount' => 475,
            'start_date' => now()->subDays(7), 'end_date' => now(),
            'status' => 1, 'settled_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->adminPost('/api/v1/admin/finance/settlement/' . $settlementId . '/confirm', []);
        $this->assertNotEquals(200, $response->json('code'), '已结算的结算单不能重复确认');
    }
}
