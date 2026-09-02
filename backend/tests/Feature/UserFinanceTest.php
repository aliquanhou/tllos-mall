<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;
use Illuminate\Support\Facades\DB;

class UserFinanceTest extends BaseModuleTest
{
    protected function createTestUser($balance = 0, $points = 0)
    {
        return DB::table('users')->insertGetId([
            'account' => 'testuser' . time() . rand(100, 999),
            'mobile' => '139' . time() . rand(100, 999),
            'nickname' => '测试用户' . rand(100, 999),
            'password' => bcrypt('123456'),
            'balance' => $balance,
            'points' => $points,
            'level_id' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createTestRecharge($userId, $amount = 100, $giveAmount = 10, $status = 0)
    {
        return DB::table('user_recharges')->insertGetId([
            'user_id' => $userId,
            'pay_no' => 'RC' . time() . rand(1000, 9999),
            'amount' => $amount,
            'give_amount' => $giveAmount,
            'pay_type' => 'wechat',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createTestWithdraw($userId, $amount = 50, $status = 0)
    {
        return DB::table('withdraws')->insertGetId([
            'user_id' => $userId,
            'amount' => $amount,
            'fee' => 0,
            'actual_amount' => $amount,
            'pay_type' => 'alipay',
            'pay_account' => 'test@test.com',
            'real_name' => '测试用户',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ========== 1. 积分签到 ==========
    public function test_user_sign_in_grants_points()
    {
        // 清理用户2今日签到记录
        DB::table('user_point_logs')->where('user_id', 2)->where('type', 'sign')->whereDate('created_at', date('Y-m-d'))->delete();
        $pointsBefore = DB::table('users')->where('id', 2)->value('points');

        $this->userLogin();
        $response = $this->userPost('/api/v1/user/points/sign');
        $this->assertApiSuccess($response, '用户签到');

        $user = DB::table('users')->where('id', 2)->first();
        $this->assertEquals($pointsBefore + 10, $user->points, '签到后积分应增加10');

        $log = DB::table('user_point_logs')->where('user_id', 2)->where('type', 'sign')->whereDate('created_at', date('Y-m-d'))->first();
        $this->assertNotNull($log, '签到应记录积分日志');
    }

    public function test_user_cannot_sign_twice_per_day()
    {
        // 清理用户2今日签到记录
        DB::table('user_point_logs')->where('user_id', 2)->where('type', 'sign')->whereDate('created_at', date('Y-m-d'))->delete();
        $this->userLogin();
        $this->userPost('/api/v1/user/points/sign');
        $response = $this->userPost('/api/v1/user/points/sign');
        $this->assertNotEquals(200, $response->json('code'), '每日只能签到一次');
    }

    // ========== 2. 积分分享 ==========
    public function test_user_share_grants_points()
    {
        $pointsBefore = DB::table('users')->where('id', 2)->value('points');
        $this->userLogin();
        $response = $this->userPost('/api/v1/user/points/share', ['product_id' => 1]);
        $this->assertApiSuccess($response, '用户分享');

        $user = DB::table('users')->where('id', 2)->first();
        $this->assertEquals($pointsBefore + 5, $user->points, '分享后积分应增加5');
    }

    // ========== 3. 积分查询 ==========
    public function test_user_my_points_returns_correct_data()
    {
        $userId = $this->createTestUser(0, 100);
        $this->userLogin($userId);
        $response = $this->userGet('/api/v1/user/points/my');
        $this->assertApiSuccess($response, '查询我的积分');
        $data = $response->json('data');
        $this->assertArrayHasKey('points', $data);
        $this->assertArrayHasKey('logs', $data);
        $this->assertArrayHasKey('today_signed', $data);
        $this->assertIsInt($data['points'], '积分应为整数');
    }

    // ========== 4. 积分日志列表 ==========
    public function test_point_log_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/user-points');
        $this->assertApiSuccess($response, '积分日志列表');
    }

    // ========== 5. 充值确认（人工补单） ==========
    public function test_recharge_confirm_increases_balance()
    {
        $userId = $this->createTestUser(0, 0);
        $rechargeId = $this->createTestRecharge($userId, 100, 10, 0);

        $balanceBefore = DB::table('users')->where('id', $userId)->value('balance');

        $response = $this->adminPost('/api/v1/admin/user-center/recharges/' . $rechargeId . '/confirm', [
            'remark' => '人工补单测试',
        ]);
        $this->assertApiSuccess($response, '充值确认');

        $user = DB::table('users')->where('id', $userId)->first();
        $this->assertEquals($balanceBefore + 110, $user->balance, '充值100+赠送10，余额应增加110');

        $recharge = DB::table('user_recharges')->where('id', $rechargeId)->first();
        $this->assertEquals(1, $recharge->status, '充值单状态应为1(已支付)');
        $this->assertNotNull($recharge->paid_at, '支付时间应记录');
    }

    public function test_recharge_confirm_creates_balance_log()
    {
        $userId = $this->createTestUser(0, 0);
        $rechargeId = $this->createTestRecharge($userId, 100, 10, 0);

        $this->adminPost('/api/v1/admin/user-center/recharges/' . $rechargeId . '/confirm', []);

        $log = DB::table('user_balance_logs')->where('user_id', $userId)->where('type', 1)->first();
        $this->assertNotNull($log, '充值应记录余额变动日志');
        $this->assertEquals(110, $log->amount, '日志金额应为110(充值100+赠送10)');
        $this->assertEquals(0, $log->balance_before, '变动前余额应为0');
        $this->assertEquals(110, $log->balance_after, '变动后余额应为110');
    }

    public function test_recharge_confirm_sends_notification()
    {
        $userId = $this->createTestUser(0, 0);
        $rechargeId = $this->createTestRecharge($userId, 100, 10, 0);

        $this->adminPost('/api/v1/admin/user-center/recharges/' . $rechargeId . '/confirm', []);

        $notification = DB::table('user_notifications')->where('user_id', $userId)->where('title', '充值到账通知')->first();
        $this->assertNotNull($notification, '充值确认应发送通知');
    }

    public function test_cannot_confirm_paid_recharge()
    {
        $userId = $this->createTestUser(0, 0);
        $rechargeId = $this->createTestRecharge($userId, 100, 10, 1);

        $response = $this->adminPost('/api/v1/admin/user-center/recharges/' . $rechargeId . '/confirm', []);
        $this->assertNotEquals(200, $response->json('code'), '已支付的充值单不能重复确认');
    }

    // ========== 6. 充值列表 ==========
    public function test_recharge_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/user-center/recharges');
        $this->assertApiSuccess($response, '充值列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('total_amount', $data['stats']);
    }

    // ========== 7. 提现审核通过 ==========
    public function test_withdraw_audit_approve_changes_status_to_1()
    {
        $userId = $this->createTestUser(100, 0);
        $withdrawId = $this->createTestWithdraw($userId, 50, 0);

        $response = $this->adminPost('/api/v1/admin/user-center/withdraws/' . $withdrawId . '/audit', [
            'status' => 1,
            'audit_remark' => '审核通过',
        ]);
        $this->assertApiSuccess($response, '提现审核通过');

        $withdraw = DB::table('withdraws')->where('id', $withdrawId)->first();
        $this->assertEquals(1, $withdraw->status, '审核通过后状态应为1');
        $this->assertNotNull($withdraw->audit_at, '审核时间应记录');
    }

    // ========== 8. 提现审核拒绝（余额退回） ==========
    public function test_withdraw_audit_reject_returns_balance()
    {
        $userId = $this->createTestUser(50, 0);
        $withdrawId = $this->createTestWithdraw($userId, 50, 0);

        $balanceBefore = DB::table('users')->where('id', $userId)->value('balance');

        $response = $this->adminPost('/api/v1/admin/user-center/withdraws/' . $withdrawId . '/audit', [
            'status' => 2,
            'audit_remark' => '审核拒绝',
        ]);
        $this->assertApiSuccess($response, '提现审核拒绝');

        $withdraw = DB::table('withdraws')->where('id', $withdrawId)->first();
        $this->assertEquals(2, $withdraw->status, '审核拒绝后状态应为2');

        $user = DB::table('users')->where('id', $userId)->first();
        $this->assertEquals($balanceBefore + 50, $user->balance, '拒绝后余额应退回50元');
    }

    public function test_cannot_audit_withdraw_twice()
    {
        $userId = $this->createTestUser(100, 0);
        $withdrawId = $this->createTestWithdraw($userId, 50, 1);

        $response = $this->adminPost('/api/v1/admin/user-center/withdraws/' . $withdrawId . '/audit', [
            'status' => 1,
        ]);
        $this->assertNotEquals(200, $response->json('code'), '已审核的提现不能重复审核');
    }

    // ========== 9. 提现打款 ==========
    public function test_withdraw_pay_changes_status_to_3()
    {
        $userId = $this->createTestUser(100, 0);
        $withdrawId = $this->createTestWithdraw($userId, 50, 1);

        $response = $this->adminPost('/api/v1/admin/user-center/withdraws/' . $withdrawId . '/pay', []);
        $this->assertApiSuccess($response, '提现打款');

        $withdraw = DB::table('withdraws')->where('id', $withdrawId)->first();
        $this->assertEquals(3, $withdraw->status, '打款成功后状态应为3');
        $this->assertNotNull($withdraw->paid_at, '打款时间应记录');
        $this->assertNotNull($withdraw->pay_no, '打款单号应生成');
    }

    public function test_withdraw_pay_failure_records_reason()
    {
        $userId = $this->createTestUser(100, 0);
        $withdrawId = $this->createTestWithdraw($userId, 50, 1);

        $response = $this->adminPost('/api/v1/admin/user-center/withdraws/' . $withdrawId . '/pay', [
            'is_fail' => 1,
            'failure_reason' => '账户异常',
        ]);
        $this->assertNotEquals(200, $response->json('code'), '打款失败应返回错误');

        $withdraw = DB::table('withdraws')->where('id', $withdrawId)->first();
        $this->assertEquals('账户异常', $withdraw->failure_reason, '失败原因应记录');
        $this->assertEquals(1, $withdraw->retry_count, '重试次数应增加');
    }

    public function test_cannot_pay_unapproved_withdraw()
    {
        $userId = $this->createTestUser(100, 0);
        $withdrawId = $this->createTestWithdraw($userId, 50, 0);

        $response = $this->adminPost('/api/v1/admin/user-center/withdraws/' . $withdrawId . '/pay', []);
        $this->assertNotEquals(200, $response->json('code'), '未审核的提现不能打款');
    }

    // ========== 10. 提现列表 ==========
    public function test_withdraw_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/user-center/withdraws');
        $this->assertApiSuccess($response, '提现列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('pending_count', $data['stats']);
    }

    // ========== 11. 账户日志列表 ==========
    public function test_account_log_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/user-center/account-logs');
        $this->assertApiSuccess($response, '账户日志列表');
    }

    // ========== 12. 余额变动一致性 ==========
    public function test_balance_change_consistency()
    {
        $userId = $this->createTestUser(0, 0);
        $rechargeId = $this->createTestRecharge($userId, 100, 0, 0);

        $this->adminPost('/api/v1/admin/user-center/recharges/' . $rechargeId . '/confirm', []);

        $user = DB::table('users')->where('id', $userId)->first();
        $log = DB::table('user_balance_logs')->where('user_id', $userId)->first();

        $this->assertEquals($log->balance_after, $user->balance, '用户余额应与日志变动后余额一致');
        $this->assertEquals($log->amount, $user->balance - $log->balance_before, '变动金额应等于余额差值');
    }

    // ========== 13. 未授权访问 ==========
    public function test_user_finance_requires_auth()
    {
        $response = $this->get('/api/v1/admin/user-center/recharges');
        $this->assertContains($response->status(), [401, 500, 302], '未授权应返回401');
    }
}
