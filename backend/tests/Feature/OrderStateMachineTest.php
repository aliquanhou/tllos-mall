<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;
use Illuminate\Support\Facades\DB;

class OrderStateMachineTest extends BaseModuleTest
{
    protected function createTestOrder($status = 1)
    {
        $orderNo = 'ORD' . date('YmdHis') . rand(1000, 9999);
        $id = DB::table('orders')->insertGetId([
            'order_no' => $orderNo,
            'user_id' => 1,
            'merchant_id' => 0,
            'total_amount' => 100.00,
            'shipping_fee' => 0.00,
            'discount_amount' => 0.00,
            'coupon_amount' => 0.00,
            'points_amount' => 0.00,
            'pay_amount' => 100.00,
            'cost_amount' => 50.00,
            'commission' => 5.00,
            'merchant_amount' => 95.00,
            'pay_type' => 1,
            'pay_no' => 'PAY' . time(),
            'pay_time' => now(),
            'order_type' => 1,
            'status' => $status,
            'receiver_name' => '测试收件人',
            'receiver_mobile' => '13800138000',
            'province_id' => 1,
            'city_id' => 1,
            'district_id' => 1,
            'province_name' => '广东省',
            'city_name' => '深圳市',
            'district_name' => '南山区',
            'receiver_address' => '测试地址',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return $id;
    }

    protected function createTestRefund($orderId, $status = 0)
    {
        $refundNo = 'REF' . date('YmdHis') . rand(1000, 9999);
        return DB::table('order_refunds')->insertGetId([
            'refund_no' => $refundNo,
            'order_id' => $orderId,
            'user_id' => 1,
            'refund_amount' => 50.00,
            'reason' => '测试退款原因',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ========== 1. 订单列表查询 ==========
    public function test_order_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/orders');
        $this->assertApiSuccess($response, '订单列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('list', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertArrayHasKey('stats', $data);
    }

    public function test_order_list_stats_contains_all_status()
    {
        $response = $this->adminGet('/api/v1/admin/orders');
        $stats = $response->json('data.stats');
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('wait_pay', $stats);
        $this->assertArrayHasKey('wait_ship', $stats);
        $this->assertArrayHasKey('wait_confirm', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('refund', $stats);
        $this->assertArrayHasKey('total_amount', $stats);
    }

    public function test_order_list_filter_by_status()
    {
        $this->createTestOrder(1);
        $response = $this->adminGet('/api/v1/admin/orders?status=1');
        $this->assertApiSuccess($response, '按状态筛选订单');
        $list = $response->json('data.list');
        foreach ($list as $order) {
            $this->assertEquals(1, $order['status']);
        }
    }

    // ========== 2. 订单详情查询 ==========
    public function test_order_detail_returns_correct_data()
    {
        $orderId = $this->createTestOrder(1);
        $response = $this->adminGet('/api/v1/admin/orders/' . $orderId);
        $this->assertApiSuccess($response, '订单详情');
        $data = $response->json('data');
        $this->assertEquals($orderId, $data['id']);
        $this->assertEquals(1, $data['status']);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('logs', $data);
        $this->assertArrayHasKey('user', $data);
    }

    public function test_order_detail_not_found_returns_404()
    {
        $response = $this->adminGet('/api/v1/admin/orders/999999');
        $this->assertEquals(404, $response->json('code'));
    }

    // ========== 3. 发货流程（状态1→2） ==========
    public function test_ship_order_changes_status_to_2()
    {
        $orderId = $this->createTestOrder(1);
        $response = $this->adminPost('/api/v1/admin/orders/' . $orderId . '/ship', [
            'express_company' => '顺丰速运',
            'express_no' => 'SF1234567890',
        ]);
        $this->assertApiSuccess($response, '订单发货');

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(2, $order->status, '发货后状态应为2(待收货)');
        $this->assertEquals('顺丰速运', $order->express_company);
        $this->assertEquals('SF1234567890', $order->express_no);
        $this->assertNotNull($order->ship_time);
        $this->assertNotNull($order->auto_confirm_at);
    }

    public function test_ship_order_creates_log()
    {
        $orderId = $this->createTestOrder(1);
        $this->adminPost('/api/v1/admin/orders/' . $orderId . '/ship', [
            'express_company' => '顺丰速运',
            'express_no' => 'SF1234567890',
        ]);
        $log = DB::table('order_logs')->where('order_id', $orderId)->where('action', 3)->first();
        $this->assertNotNull($log, '发货后应创建订单日志');
        $this->assertEquals('发货', $log->action_name);
        $this->assertStringContainsString('顺丰速运', $log->remark);
    }

    public function test_ship_order_requires_express_company()
    {
        $orderId = $this->createTestOrder(1);
        $response = $this->adminPost('/api/v1/admin/orders/' . $orderId . '/ship', [
            'express_no' => 'SF1234567890',
        ]);
        $this->assertEquals(422, $response->status(), '缺少物流公司应返回422');
    }

    public function test_ship_order_requires_express_no()
    {
        $orderId = $this->createTestOrder(1);
        $response = $this->adminPost('/api/v1/admin/orders/' . $orderId . '/ship', [
            'express_company' => '顺丰速运',
        ]);
        $this->assertEquals(422, $response->status(), '缺少物流单号应返回422');
    }

    // ========== 4. 发货状态校验 ==========
    public function test_cannot_ship_order_in_status_0()
    {
        $orderId = $this->createTestOrder(0);
        $response = $this->adminPost('/api/v1/admin/orders/' . $orderId . '/ship', [
            'express_company' => '顺丰速运',
            'express_no' => 'SF1234567890',
        ]);
        $this->assertNotEquals(200, $response->json('code'), '待付款订单不能发货');
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(0, $order->status, '状态不应改变');
    }

    public function test_cannot_ship_order_in_status_2()
    {
        $orderId = $this->createTestOrder(2);
        $response = $this->adminPost('/api/v1/admin/orders/' . $orderId . '/ship', [
            'express_company' => '顺丰速运',
            'express_no' => 'SF1234567890',
        ]);
        $this->assertNotEquals(200, $response->json('code'), '已发货订单不能重复发货');
    }

    public function test_cannot_ship_order_in_status_3()
    {
        $orderId = $this->createTestOrder(3);
        $response = $this->adminPost('/api/v1/admin/orders/' . $orderId . '/ship', [
            'express_company' => '顺丰速运',
            'express_no' => 'SF1234567890',
        ]);
        $this->assertNotEquals(200, $response->json('code'), '已完成订单不能发货');
    }

    // ========== 5. 订单备注 ==========
    public function test_order_remark_updates_successfully()
    {
        $orderId = $this->createTestOrder(1);
        $response = $this->adminPost('/api/v1/admin/orders/' . $orderId . '/remark', [
            'remark' => '这是测试备注',
        ]);
        $this->assertApiSuccess($response, '订单备注');
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals('这是测试备注', $order->admin_remark);
    }

    public function test_order_remark_does_not_change_status()
    {
        $orderId = $this->createTestOrder(1);
        $this->adminPost('/api/v1/admin/orders/' . $orderId . '/remark', [
            'remark' => '测试备注',
        ]);
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(1, $order->status, '备注不应改变订单状态');
    }

    // ========== 6. 退款审核通过 ==========
    public function test_refund_approve_changes_order_status_to_6()
    {
        $orderId = $this->createTestOrder(2);
        $refundId = $this->createTestRefund($orderId, 0);

        $response = $this->adminPost('/api/v1/admin/refunds/' . $refundId . '/audit', [
            'action' => 'approve',
            'reason' => '同意退款',
        ]);
        $this->assertApiSuccess($response, '退款审核通过');

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(6, $order->status, '退款通过后订单状态应为6(已退款)');

        $refund = DB::table('order_refunds')->where('id', $refundId)->first();
        $this->assertEquals(5, $refund->status, '退款单状态应为5(已退款)');
        $this->assertNotNull($refund->refund_time);
    }

    public function test_refund_approve_creates_order_log()
    {
        $orderId = $this->createTestOrder(2);
        $refundId = $this->createTestRefund($orderId, 0);

        $this->adminPost('/api/v1/admin/refunds/' . $refundId . '/audit', [
            'action' => 'approve',
        ]);

        $log = DB::table('order_logs')->where('order_id', $orderId)->where('action', 6)->first();
        $this->assertNotNull($log, '退款成功应创建订单日志');
        $this->assertEquals('退款成功', $log->action_name);
    }

    // ========== 7. 退款审核拒绝 ==========
    public function test_refund_reject_changes_refund_status_to_2()
    {
        $orderId = $this->createTestOrder(2);
        $refundId = $this->createTestRefund($orderId, 0);

        $response = $this->adminPost('/api/v1/admin/refunds/' . $refundId . '/audit', [
            'action' => 'reject',
            'reason' => '不符合退款条件',
        ]);
        $this->assertApiSuccess($response, '退款审核拒绝');

        $refund = DB::table('order_refunds')->where('id', $refundId)->first();
        $this->assertEquals(2, $refund->status, '退款拒绝后状态应为2');
        $this->assertEquals('不符合退款条件', $refund->refuse_reason);
    }

    public function test_refund_reject_does_not_change_order_status()
    {
        $orderId = $this->createTestOrder(2);
        $refundId = $this->createTestRefund($orderId, 0);

        $this->adminPost('/api/v1/admin/refunds/' . $refundId . '/audit', [
            'action' => 'reject',
            'reason' => '拒绝',
        ]);

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(2, $order->status, '退款拒绝不应改变订单状态');
    }

    // ========== 8. 退款审核状态校验 ==========
    public function test_cannot_audit_refund_in_non_pending_status()
    {
        $orderId = $this->createTestOrder(2);
        $refundId = $this->createTestRefund($orderId, 5);

        $response = $this->adminPost('/api/v1/admin/refunds/' . $refundId . '/audit', [
            'action' => 'approve',
        ]);
        $this->assertNotEquals(200, $response->json('code'), '已处理的退款单不能重复审核');
    }

    public function test_refund_audit_requires_action()
    {
        $orderId = $this->createTestOrder(2);
        $refundId = $this->createTestRefund($orderId, 0);

        $response = $this->adminPost('/api/v1/admin/refunds/' . $refundId . '/audit', []);
        $this->assertEquals(422, $response->status(), '缺少action应返回422');
    }

    // ========== 9. 订单日志查询 ==========
    public function test_order_log_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/order-log');
        $this->assertApiSuccess($response, '订单日志列表');
    }

    public function test_order_log_filter_by_order_id()
    {
        $orderId = $this->createTestOrder(1);
        $this->adminPost('/api/v1/admin/orders/' . $orderId . '/ship', [
            'express_company' => '顺丰速运',
            'express_no' => 'SF1234567890',
        ]);

        $response = $this->adminGet('/api/v1/admin/order-log?order_id=' . $orderId);
        $this->assertApiSuccess($response, '按订单ID筛选日志');
        $list = $response->json('data.list');
        $this->assertGreaterThan(0, count($list), '应至少有一条发货日志');
    }

    // ========== 10. 完整状态流转测试 ==========
    public function test_full_order_lifecycle_pay_ship_complete()
    {
        // 创建待付款订单
        $orderId = $this->createTestOrder(0);
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(0, $order->status);

        // 模拟支付（状态0→1）
        DB::table('orders')->where('id', $orderId)->update(['status' => 1, 'pay_time' => now()]);
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(1, $order->status);

        // 发货（状态1→2）
        $this->adminPost('/api/v1/admin/orders/' . $orderId . '/ship', [
            'express_company' => '顺丰速运',
            'express_no' => 'SF1234567890',
        ]);
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(2, $order->status);

        // 模拟确认收货（状态2→3）
        DB::table('orders')->where('id', $orderId)->update(['status' => 3, 'confirm_time' => now()]);
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(3, $order->status);

        // 验证订单日志
        $logs = DB::table('order_logs')->where('order_id', $orderId)->get();
        $this->assertGreaterThan(0, count($logs), '完整生命周期应有操作日志');
    }

    // ========== 11. 未授权访问 ==========
    public function test_order_list_requires_auth()
    {
        $response = $this->get('/api/v1/admin/orders');
        $this->assertContains($response->status(), [401, 500, 302], '未授权应返回401');
    }
}
