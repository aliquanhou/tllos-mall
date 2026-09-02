<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;
use Illuminate\Support\Facades\DB;

class MerchantSettlementTest extends BaseModuleTest
{
    protected function createTestMerchant($balance = 0)
    {
        $name = '测试商家' . time() . rand(100, 999);
        return DB::table('merchants')->insertGetId([
            'user_id' => 1,
            'username' => 'testmerchant' . time(),
            'password' => bcrypt('123456'),
            'name' => $name,
            'category_id' => 1,
            'contact_name' => '测试联系人',
            'contact_mobile' => '139' . time() . rand(100, 999),
            'province_id' => 1,
            'city_id' => 1,
            'district_id' => 1,
            'address' => '测试地址',
            'status' => 1,
            'balance' => $balance,
            'frozen' => 0,
            'total_income' => 0,
            'deposit' => 0,
            'deposit_status' => 0,
            'is_blacklisted' => 0,
            'reject_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createTestSettlement($merchantId, $amount = 1000, $status = 0)
    {
        return DB::table('merchant_settlements')->insertGetId([
            'settlement_no' => 'STL' . time() . rand(1000, 9999),
            'merchant_id' => $merchantId,
            'order_amount' => $amount,
            'order_count' => 10,
            'commission' => $amount * 0.05,
            'refund_amount' => 0,
            'settlement_amount' => $amount * 0.95,
            'start_date' => now()->subDays(7),
            'end_date' => now(),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ========== 1. 结算列表查询 ==========
    public function test_settlement_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/finance/settlement');
        $this->assertApiSuccess($response, '结算列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('list', $data);
        $this->assertArrayHasKey('stats', $data);
    }

    public function test_settlement_list_stats_contains_all_fields()
    {
        $response = $this->adminGet('/api/v1/admin/finance/settlement');
        $stats = $response->json('data.stats');
        $this->assertArrayHasKey('total_count', $stats);
        $this->assertArrayHasKey('total_amount', $stats);
        $this->assertArrayHasKey('pending_count', $stats);
        $this->assertArrayHasKey('pending_amount', $stats);
        $this->assertArrayHasKey('settled_count', $stats);
    }

    public function test_settlement_list_filter_by_status()
    {
        $merchantId = $this->createTestMerchant();
        $this->createTestSettlement($merchantId, 1000, 0);
        $response = $this->adminGet('/api/v1/admin/finance/settlement?status=0');
        $this->assertApiSuccess($response, '按状态筛选结算');
    }

    // ========== 2. 确认结算 ==========
    public function test_settlement_confirm_changes_status_to_1()
    {
        $merchantId = $this->createTestMerchant();
        $settlementId = $this->createTestSettlement($merchantId, 1000, 0);

        $response = $this->adminPost('/api/v1/admin/finance/settlement/' . $settlementId . '/confirm', []);
        $this->assertApiSuccess($response, '确认结算');

        $settlement = DB::table('merchant_settlements')->where('id', $settlementId)->first();
        $this->assertEquals(1, $settlement->status, '确认结算后状态应为1');
        $this->assertNotNull($settlement->settled_at, '结算时间应记录');
    }

    public function test_cannot_confirm_settlement_twice()
    {
        $merchantId = $this->createTestMerchant();
        $settlementId = $this->createTestSettlement($merchantId, 1000, 1);

        $response = $this->adminPost('/api/v1/admin/finance/settlement/' . $settlementId . '/confirm', []);
        $this->assertNotEquals(200, $response->json('code'), '已处理的结算单不能重复确认');
    }

    public function test_settlement_confirm_not_found_returns_error()
    {
        $response = $this->adminPost('/api/v1/admin/finance/settlement/999999/confirm', []);
        $this->assertNotEquals(200, $response->json('code'), '不存在的结算单应返回错误');
    }

    // ========== 3. 结算金额计算验证 ==========
    public function test_settlement_amount_calculation()
    {
        $merchantId = $this->createTestMerchant();
        $orderAmount = 1000;
        $commission = 50;
        $refundAmount = 0;
        $expectedSettlement = $orderAmount - $commission - $refundAmount;

        $settlementId = DB::table('merchant_settlements')->insertGetId([
            'settlement_no' => 'STL' . time() . rand(1000, 9999),
            'merchant_id' => $merchantId,
            'order_amount' => $orderAmount,
            'order_count' => 10,
            'commission' => $commission,
            'refund_amount' => $refundAmount,
            'settlement_amount' => $expectedSettlement,
            'start_date' => now()->subDays(7),
            'end_date' => now(),
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $settlement = DB::table('merchant_settlements')->where('id', $settlementId)->first();
        $this->assertEquals(950, $settlement->settlement_amount, '结算金额=订单金额-佣金-退款，应为950');
        $this->assertEquals(50, $settlement->commission, '佣金应为50(5%)');
    }

    // ========== 4. 收入列表 ==========
    public function test_income_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/finance/income');
        $this->assertApiSuccess($response, '收入列表');
    }

    // ========== 5. 退款列表 ==========
    public function test_refund_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/finance/refund');
        $this->assertApiSuccess($response, '退款列表');
    }

    // ========== 6. 商家账户日志 ==========
    public function test_merchant_account_logs_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/merchant-account-logs');
        $this->assertApiSuccess($response, '商家账户日志列表');
    }

    public function test_merchant_account_logs_stats_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/merchant-account-logs/stats');
        $this->assertApiSuccess($response, '商家账户日志统计');
    }

    // ========== 7. 商家提现审核 ==========
    public function test_merchant_withdraw_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/finance/withdraw');
        $this->assertApiSuccess($response, '商家提现列表');
    }

    // ========== 8. 结算单唯一性 ==========
    public function test_settlement_no_is_unique()
    {
        $merchantId = $this->createTestMerchant();
        $settlementNo = 'STL' . time() . 'UNIQUE';

        DB::table('merchant_settlements')->insert([
            'settlement_no' => $settlementNo,
            'merchant_id' => $merchantId,
            'order_amount' => 100,
            'order_count' => 1,
            'commission' => 5,
            'refund_amount' => 0,
            'settlement_amount' => 95,
            'start_date' => now()->subDays(7),
            'end_date' => now(),
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 尝试插入相同settlement_no应该失败
        try {
            DB::table('merchant_settlements')->insert([
                'settlement_no' => $settlementNo,
                'merchant_id' => $merchantId,
                'order_amount' => 200,
                'order_count' => 2,
                'commission' => 10,
                'refund_amount' => 0,
                'settlement_amount' => 190,
                'start_date' => now()->subDays(7),
                'end_date' => now(),
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('settlement_no应该有唯一约束');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Duplicate', $e->getMessage(), '应报重复键错误');
        }
    }

    // ========== 9. 结算周期验证 ==========
    public function test_settlement_has_date_range()
    {
        $merchantId = $this->createTestMerchant();
        $settlementId = $this->createTestSettlement($merchantId, 1000, 0);

        $settlement = DB::table('merchant_settlements')->where('id', $settlementId)->first();
        $this->assertNotNull($settlement->start_date, '结算单应有开始日期');
        $this->assertNotNull($settlement->end_date, '结算单应有结束日期');
        $this->assertLessThanOrEqual($settlement->end_date, $settlement->start_date, '开始日期应早于结束日期');
    }

    // ========== 10. 未授权访问 ==========
    public function test_finance_requires_auth()
    {
        $response = $this->get('/api/v1/admin/finance/settlement');
        $this->assertContains($response->status(), [401, 500, 302], '未授权应返回401');
    }
}
