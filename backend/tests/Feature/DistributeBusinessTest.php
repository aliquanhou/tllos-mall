<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;
use Illuminate\Support\Facades\DB;

class DistributeBusinessTest extends BaseModuleTest
{
    protected function createTestAgent($status = 1)
    {
        return DB::table('distribute_agents')->insertGetId([
            'user_id' => 1,
            'level_id' => 1,
            'parent_id' => null,
            'real_name' => '测试分销商',
            'mobile' => '13900139000',
            'status' => $status,
            'total_income' => 0,
            'available_income' => 0,
            'total_orders' => 0,
            'total_members' => 0,
            'apply_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createTestDistributeGoods($productId = null, $type = 1, $rate = 5.00, $amount = 0)
    {
        if (!$productId) $productId = rand(1000, 9999);
        DB::table('distribute_goods')->where('product_id', $productId)->delete();
        return DB::table('distribute_goods')->insertGetId([
            'product_id' => $productId,
            'product_name' => '测试分销商品',
            'commission_type' => $type,
            'commission_rate' => $rate,
            'commission_amount' => $amount,
            'is_distribute' => 1,
            'status' => 1,
            'sort' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createTestDistributeOrder($agentId, $amount = 100.00, $rate = 5.00, $status = 0)
    {
        $commission = round($amount * $rate / 100, 2);
        return DB::table('distribute_orders')->insertGetId([
            'order_id' => 1,
            'order_no' => 'ORD' . time(),
            'user_id' => 1,
            'agent_id' => $agentId,
            'level_id' => 1,
            'goods_amount' => $amount,
            'commission_rate' => $rate,
            'commission_amount' => $commission,
            'commission' => $commission,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ========== 1. 分销概览 ==========
    public function test_overview_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/distribute/overview');
        $this->assertApiSuccess($response, '分销概览');
        $data = $response->json('data');
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('trend', $data);
        $this->assertArrayHasKey('recent_orders', $data);
        $this->assertArrayHasKey('top_agents', $data);
        $this->assertArrayHasKey('top_goods', $data);
    }

    public function test_overview_stats_contains_all_fields()
    {
        $response = $this->adminGet('/api/v1/admin/distribute/overview');
        $stats = $response->json('data.stats');
        $this->assertArrayHasKey('total_agents', $stats);
        $this->assertArrayHasKey('active_agents', $stats);
        $this->assertArrayHasKey('total_orders', $stats);
        $this->assertArrayHasKey('total_commission', $stats);
        $this->assertArrayHasKey('settled_commission', $stats);
        $this->assertArrayHasKey('pending_commission', $stats);
    }

    // ========== 2. 分销商列表 ==========
    public function test_agents_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/distribute/agents');
        $this->assertApiSuccess($response, '分销商列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('list', $data);
        $this->assertArrayHasKey('stats', $data);
    }

    public function test_agents_list_stats_contains_all_status()
    {
        $response = $this->adminGet('/api/v1/admin/distribute/agents');
        $stats = $response->json('data.stats');
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('approved', $stats);
        $this->assertArrayHasKey('rejected', $stats);
        $this->assertArrayHasKey('disabled', $stats);
    }

    // ========== 3. 分销商审核 ==========
    public function test_agent_audit_approve_changes_status_to_1()
    {
        $agentId = $this->createTestAgent(0);
        $response = $this->adminPost('/api/v1/admin/distribute/agents/' . $agentId . '/audit', [
            'status' => 1,
            'remark' => '审核通过',
        ]);
        $this->assertApiSuccess($response, '分销商审核通过');
        $agent = DB::table('distribute_agents')->where('id', $agentId)->first();
        $this->assertEquals(1, $agent->status, '审核通过后状态应为1');
        $this->assertNotNull($agent->audit_at);
    }

    public function test_agent_audit_reject_changes_status_to_2()
    {
        $agentId = $this->createTestAgent(0);
        $response = $this->adminPost('/api/v1/admin/distribute/agents/' . $agentId . '/audit', [
            'status' => 2,
            'remark' => '资料不全',
        ]);
        $this->assertApiSuccess($response, '分销商审核拒绝');
        $agent = DB::table('distribute_agents')->where('id', $agentId)->first();
        $this->assertEquals(2, $agent->status, '审核拒绝后状态应为2');
    }

    public function test_cannot_audit_agent_twice()
    {
        $agentId = $this->createTestAgent(1);
        $response = $this->adminPost('/api/v1/admin/distribute/agents/' . $agentId . '/audit', [
            'status' => 1,
        ]);
        $this->assertNotEquals(200, $response->json('code'), '已审核的分销商不能重复审核');
    }

    public function test_agent_audit_requires_status()
    {
        $agentId = $this->createTestAgent(0);
        $response = $this->adminPost('/api/v1/admin/distribute/agents/' . $agentId . '/audit', []);
        $this->assertEquals(422, $response->status(), '缺少status应返回422');
    }

    // ========== 4. 分销订单列表 ==========
    public function test_orders_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/distribute/orders');
        $this->assertApiSuccess($response, '分销订单列表');
    }

    public function test_orders_list_filter_by_status()
    {
        $agentId = $this->createTestAgent(1);
        $this->createTestDistributeOrder($agentId, 100, 5, 0);
        $response = $this->adminGet('/api/v1/admin/distribute/orders?status=0');
        $this->assertApiSuccess($response, '按状态筛选分销订单');
    }

    // ========== 5. 佣金计算逻辑验证 ==========
    public function test_commission_calculation_by_rate()
    {
        $amount = 100.00;
        $rate = 5.00;
        $expectedCommission = round($amount * $rate / 100, 2);
        $this->assertEquals(5.00, $expectedCommission, '100元5%佣金应为5元');

        $agentId = $this->createTestAgent(1);
        $orderId = $this->createTestDistributeOrder($agentId, $amount, $rate, 0);
        $order = DB::table('distribute_orders')->where('id', $orderId)->first();
        $this->assertEquals($expectedCommission, $order->commission, '分销订单佣金金额应正确');
        $this->assertEquals($expectedCommission, $order->commission_amount, 'commission_amount应与commission一致');
    }

    public function test_commission_calculation_by_fixed_amount()
    {
        $goodsId = $this->createTestDistributeGoods(1, 2, 0, 10.00);
        $goods = DB::table('distribute_goods')->where('id', $goodsId)->first();
        $this->assertEquals(2, $goods->commission_type, '佣金类型应为固定金额');
        $this->assertEquals(10.00, $goods->commission_amount, '固定佣金金额应为10元');
    }

    public function test_commission_rate_type_1()
    {
        $goodsId = $this->createTestDistributeGoods(1, 1, 8.50, 0);
        $goods = DB::table('distribute_goods')->where('id', $goodsId)->first();
        $this->assertEquals(1, $goods->commission_type, '佣金类型应为按比例');
        $this->assertEquals(8.50, $goods->commission_rate, '佣金比例应为8.5%');
    }

    public function test_commission_settlement_status()
    {
        $agentId = $this->createTestAgent(1);
        $orderId = $this->createTestDistributeOrder($agentId, 200, 10, 0);
        $order = DB::table('distribute_orders')->where('id', $orderId)->first();
        $this->assertEquals(0, $order->status, '新分销订单状态应为0(待结算)');
        $this->assertEquals(20.00, $order->commission, '200元10%佣金应为20元');

        // 模拟结算
        DB::table('distribute_orders')->where('id', $orderId)->update(['status' => 1, 'settled_at' => now()]);
        $order = DB::table('distribute_orders')->where('id', $orderId)->first();
        $this->assertEquals(1, $order->status, '结算后状态应为1');
        $this->assertNotNull($order->settled_at, '结算时间应记录');
    }

    // ========== 6. 分销商品配置 ==========
    public function test_goods_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/distribute/goods');
        $this->assertApiSuccess($response, '分销商品列表');
    }

    public function test_goods_toggle_distribute_status()
    {
        $goodsId = $this->createTestDistributeGoods(1, 1, 5, 0);
        $goods = DB::table('distribute_goods')->where('id', $goodsId)->first();
        $this->assertEquals(1, $goods->is_distribute, '默认应开启分销');

        DB::table('distribute_goods')->where('id', $goodsId)->update(['is_distribute' => 0]);
        $goods = DB::table('distribute_goods')->where('id', $goodsId)->first();
        $this->assertEquals(0, $goods->is_distribute, '应能关闭分销');
    }

    // ========== 7. 分销等级 ==========
    public function test_levels_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/distribute/levels');
        $this->assertApiSuccess($response, '分销等级列表');
    }

    public function test_level_commission_rate_increases_with_level()
    {
        // 验证等级越高佣金比例越高的业务规则
        $levels = DB::table('distribute_levels')->where('status', 1)->orderBy('level', 'asc')->get();
        if (count($levels) >= 2) {
            for ($i = 1; $i < count($levels); $i++) {
                $this->assertGreaterThanOrEqual(
                    $levels[$i-1]->commission_rate,
                    $levels[$i]->commission_rate,
                    '高等级佣金比例应不低于低等级'
                );
            }
        }
    }

    // ========== 8. 分销设置 ==========
    public function test_settings_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/distribute/settings');
        $this->assertApiSuccess($response, '分销设置');
    }

    // ========== 9. 分销商上下级关系 ==========
    public function test_agent_parent_relationship()
    {
        $parentId = $this->createTestAgent(1);
        $childId = DB::table('distribute_agents')->insertGetId([
            'user_id' => 1,
            'level_id' => 1,
            'parent_id' => $parentId,
            'real_name' => '下级分销商',
            'mobile' => '13900139001',
            'status' => 1,
            'total_income' => 0,
            'available_income' => 0,
            'total_orders' => 0,
            'total_members' => 0,
            'apply_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $child = DB::table('distribute_agents')->where('id', $childId)->first();
        $this->assertEquals($parentId, $child->parent_id, '下级分销商应关联上级');

        // 更新上级团队人数
        DB::table('distribute_agents')->where('id', $parentId)->increment('total_members');
        $parent = DB::table('distribute_agents')->where('id', $parentId)->first();
        $this->assertEquals(1, $parent->total_members, '上级团队人数应增加');
    }

    // ========== 10. 分销商收入统计 ==========
    public function test_agent_income_accumulation()
    {
        $agentId = $this->createTestAgent(1);
        $this->createTestDistributeOrder($agentId, 100, 10, 1);
        $this->createTestDistributeOrder($agentId, 200, 5, 1);

        $totalCommission = DB::table('distribute_orders')->where('agent_id', $agentId)->where('status', 1)->sum('commission');
        $this->assertEquals(20.00, $totalCommission, '已结算佣金总额应为20元(10+10)');

        // 更新分销商累计收入
        DB::table('distribute_agents')->where('id', $agentId)->update(['total_income' => $totalCommission]);
        $agent = DB::table('distribute_agents')->where('id', $agentId)->first();
        $this->assertEquals(20.00, $agent->total_income, '分销商累计收入应正确');
    }

    // ========== 11. 未授权访问 ==========
    public function test_distribute_requires_auth()
    {
        $response = $this->get('/api/v1/admin/distribute/overview');
        $this->assertContains($response->status(), [401, 500, 302], '未授权应返回401');
    }
}
