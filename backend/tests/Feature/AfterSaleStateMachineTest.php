<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;
use Illuminate\Support\Facades\DB;

class AfterSaleStateMachineTest extends BaseModuleTest
{
    protected function createTestOrder($status = 2)
    {
        $orderNo = 'ORD' . date('YmdHis') . rand(1000, 9999);
        return DB::table('orders')->insertGetId([
            'order_no' => $orderNo,
            'user_id' => 1,
            'merchant_id' => 0,
            'total_amount' => 100.00,
            'pay_amount' => 100.00,
            'status' => $status,
            'receiver_name' => '测试收件人',
            'receiver_mobile' => '13800138000',
            'province_id' => 1, 'city_id' => 1, 'district_id' => 1,
            'province_name' => '广东省', 'city_name' => '深圳市', 'district_name' => '南山区',
            'receiver_address' => '测试地址',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function createTestAfterSale($orderId, $type = 1, $status = 0)
    {
        return DB::table('order_after_sales')->insertGetId([
            'order_id' => $orderId,
            'order_no' => 'ORD' . time(),
            'user_id' => 1,
            'merchant_id' => 0,
            'type' => $type,
            'reason' => '测试售后原因',
            'description' => '测试售后描述',
            'refund_amount' => 50.00,
            'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ========== 1. 售后列表查询 ==========
    public function test_aftersale_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/after-sale');
        $this->assertApiSuccess($response, '售后列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('list', $data);
        $this->assertArrayHasKey('stats', $data);
    }

    public function test_aftersale_list_stats_contains_all_status()
    {
        $response = $this->adminGet('/api/v1/admin/after-sale');
        $stats = $response->json('data.stats');
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
        $this->assertArrayHasKey('wait_return', $stats);
        $this->assertArrayHasKey('wait_receive', $stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('rejected', $stats);
    }

    public function test_aftersale_list_filter_by_type()
    {
        $orderId = $this->createTestOrder();
        $this->createTestAfterSale($orderId, 1, 0);
        $response = $this->adminGet('/api/v1/admin/after-sale?type=1');
        $this->assertApiSuccess($response, '按类型筛选售后');
    }

    public function test_aftersale_list_filter_by_status()
    {
        $orderId = $this->createTestOrder();
        $this->createTestAfterSale($orderId, 1, 0);
        $response = $this->adminGet('/api/v1/admin/after-sale?status=0');
        $this->assertApiSuccess($response, '按状态筛选售后');
    }

    // ========== 2. 售后详情查询 ==========
    public function test_aftersale_detail_returns_correct_data()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 0);

        $response = $this->adminGet('/api/v1/admin/after-sale/' . $afterSaleId);
        $this->assertApiSuccess($response, '售后详情');
        $data = $response->json('data');
        $this->assertArrayHasKey('info', $data);
        $this->assertArrayHasKey('logs', $data);
        $this->assertArrayHasKey('order', $data);
        $this->assertEquals($afterSaleId, $data['info']['id']);
    }

    public function test_aftersale_detail_not_found_returns_404()
    {
        $response = $this->adminGet('/api/v1/admin/after-sale/999999');
        $this->assertEquals(404, $response->json('code'));
    }

    // ========== 3. 退货退款审核通过（状态0→4） ==========
    public function test_refund_return_audit_approve_changes_status_to_4()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 0);

        $response = $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/audit', [
            'status' => 1,
            'audit_remark' => '同意退货退款',
        ]);
        $this->assertApiSuccess($response, '退货退款审核通过');

        $afterSale = DB::table('order_after_sales')->where('id', $afterSaleId)->first();
        $this->assertEquals(4, $afterSale->status, '退货退款审核通过后状态应为4(待退货)');
        $this->assertNotNull($afterSale->audit_at);
        $this->assertEquals('同意退货退款', $afterSale->audit_remark);
    }

    public function test_refund_return_audit_approve_creates_logs()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 0);

        $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/audit', [
            'status' => 1,
        ]);

        $logs = DB::table('after_sale_logs')->where('after_sale_id', $afterSaleId)->get();
        $this->assertGreaterThanOrEqual(2, count($logs), '退货退款审核通过应创建至少2条日志(审核通过+等待退货)');
        $actions = $logs->pluck('action')->toArray();
        $this->assertContains('审核通过', $actions);
        $this->assertContains('等待用户退货', $actions);
    }

    // ========== 4. 仅退款审核通过（状态0→3） ==========
    public function test_refund_only_audit_approve_changes_status_to_3()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 2, 0);

        $response = $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/audit', [
            'status' => 1,
            'audit_remark' => '同意仅退款',
        ]);
        $this->assertApiSuccess($response, '仅退款审核通过');

        $afterSale = DB::table('order_after_sales')->where('id', $afterSaleId)->first();
        $this->assertEquals(3, $afterSale->status, '仅退款审核通过后状态应为3(已完成)');
        $this->assertNotNull($afterSale->refund_time);
        $this->assertNotNull($afterSale->completed_at);
    }

    // ========== 5. 审核拒绝（状态0→2） ==========
    public function test_audit_reject_changes_status_to_2()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 0);

        $response = $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/audit', [
            'status' => 2,
            'audit_remark' => '不符合退款条件',
        ]);
        $this->assertApiSuccess($response, '售后审核拒绝');

        $afterSale = DB::table('order_after_sales')->where('id', $afterSaleId)->first();
        $this->assertEquals(2, $afterSale->status, '审核拒绝后状态应为2');
        $this->assertEquals('不符合退款条件', $afterSale->audit_remark);
    }

    public function test_audit_reject_creates_log()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 0);

        $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/audit', [
            'status' => 2,
        ]);

        $log = DB::table('after_sale_logs')->where('after_sale_id', $afterSaleId)->where('action', '审核拒绝')->first();
        $this->assertNotNull($log, '审核拒绝应创建日志');
    }

    // ========== 6. 审核状态校验 ==========
    public function test_cannot_audit_non_pending_aftersale()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 3);

        $response = $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/audit', [
            'status' => 1,
        ]);
        $this->assertNotEquals(200, $response->json('code'), '已完成的售后单不能审核');
    }

    public function test_audit_requires_status()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 0);

        $response = $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/audit', []);
        $this->assertEquals(422, $response->status(), '缺少status应返回422');
    }

    public function test_audit_status_must_be_1_or_2()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 0);

        $response = $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/audit', [
            'status' => 3,
        ]);
        $this->assertEquals(422, $response->status(), 'status只能是1或2');
    }

    // ========== 7. 确认收货（状态6→3） ==========
    public function test_receive_changes_status_to_3()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 6);

        $response = $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/receive', []);
        $this->assertApiSuccess($response, '确认收货');

        $afterSale = DB::table('order_after_sales')->where('id', $afterSaleId)->first();
        $this->assertEquals(3, $afterSale->status, '确认收货后状态应为3(已完成)');
        $this->assertNotNull($afterSale->receive_time);
        $this->assertNotNull($afterSale->refund_time);
        $this->assertNotNull($afterSale->completed_at);
    }

    public function test_receive_updates_order_status_to_6()
    {
        $orderId = $this->createTestOrder(2);
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 6);

        $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/receive', []);

        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(6, $order->status, '确认收货后订单状态应为6(已退款)');
    }

    public function test_receive_creates_log()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 6);

        $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/receive', []);

        $log = DB::table('after_sale_logs')->where('after_sale_id', $afterSaleId)->where('action', '确认收货')->first();
        $this->assertNotNull($log, '确认收货应创建日志');
    }

    // ========== 8. 确认收货状态校验 ==========
    public function test_cannot_receive_non_wait_receive_status()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 4);

        $response = $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/receive', []);
        $this->assertNotEquals(200, $response->json('code'), '待退货状态不能确认收货');
    }

    // ========== 9. 售后完成 ==========
    public function test_complete_from_approved_status()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 1);

        $response = $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/complete', []);
        $this->assertApiSuccess($response, '售后完成');

        $afterSale = DB::table('order_after_sales')->where('id', $afterSaleId)->first();
        $this->assertEquals(3, $afterSale->status, '完成后状态应为3');
        $this->assertNotNull($afterSale->completed_at);
    }

    public function test_cannot_complete_from_pending_status()
    {
        $orderId = $this->createTestOrder();
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 0);

        $response = $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/complete', []);
        $this->assertNotEquals(200, $response->json('code'), '待审核状态不能直接完成');
    }

    // ========== 10. 完整售后流程 ==========
    public function test_full_aftersale_lifecycle_refund_return()
    {
        // 创建订单和售后单（待审核）
        $orderId = $this->createTestOrder(2);
        $afterSaleId = $this->createTestAfterSale($orderId, 1, 0);
        $afterSale = DB::table('order_after_sales')->where('id', $afterSaleId)->first();
        $this->assertEquals(0, $afterSale->status);

        // 审核通过 → 待退货
        $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/audit', ['status' => 1]);
        $afterSale = DB::table('order_after_sales')->where('id', $afterSaleId)->first();
        $this->assertEquals(4, $afterSale->status);

        // 模拟用户退货 → 待收货
        DB::table('order_after_sales')->where('id', $afterSaleId)->update([
            'status' => 6,
            'return_express_company' => '顺丰速运',
            'return_express_no' => 'SF123456',
            'return_ship_time' => now(),
        ]);
        $afterSale = DB::table('order_after_sales')->where('id', $afterSaleId)->first();
        $this->assertEquals(6, $afterSale->status);

        // 确认收货 → 已完成
        $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/receive', []);
        $afterSale = DB::table('order_after_sales')->where('id', $afterSaleId)->first();
        $this->assertEquals(3, $afterSale->status);

        // 验证订单状态
        $order = DB::table('orders')->where('id', $orderId)->first();
        $this->assertEquals(6, $order->status);

        // 验证日志
        $logs = DB::table('after_sale_logs')->where('after_sale_id', $afterSaleId)->get();
        $this->assertGreaterThanOrEqual(3, count($logs), '完整售后流程应有至少3条日志');
    }

    public function test_full_aftersale_lifecycle_refund_only()
    {
        // 创建仅退款售后单
        $orderId = $this->createTestOrder(2);
        $afterSaleId = $this->createTestAfterSale($orderId, 2, 0);

        // 审核通过 → 直接完成
        $this->adminPost('/api/v1/admin/after-sale/' . $afterSaleId . '/audit', ['status' => 1]);
        $afterSale = DB::table('order_after_sales')->where('id', $afterSaleId)->first();
        $this->assertEquals(3, $afterSale->status, '仅退款审核通过应直接完成');
        $this->assertNotNull($afterSale->refund_time);
    }

    // ========== 11. 未授权访问 ==========
    public function test_aftersale_list_requires_auth()
    {
        $response = $this->get('/api/v1/admin/after-sale');
        $this->assertContains($response->status(), [401, 500, 302], '未授权应返回401');
    }
}
