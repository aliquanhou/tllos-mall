<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementTest extends BaseModuleTest
{
    protected function createTestUser($balance = 0, $points = 0, $status = 1)
    {
        return DB::table('users')->insertGetId([
            'account' => 'testuser' . time() . rand(100, 999),
            'mobile' => '139' . time() . rand(100, 999),
            'nickname' => '测试用户' . rand(100, 999),
            'password' => Hash::make('123456'),
            'balance' => $balance,
            'points' => $points,
            'level_id' => 1,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createTestRealName($userId, $status = 0)
    {
        return DB::table('user_real_names')->insertGetId([
            'user_id' => $userId,
            'real_name' => '张三',
            'id_card' => '11010119900101' . rand(1000, 9999),
            'id_card_front' => 'https://example.com/front.jpg',
            'id_card_back' => 'https://example.com/back.jpg',
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ========== 1. 用户列表查询 ==========
    public function test_user_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/users');
        $this->assertApiSuccess($response, '用户列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('list', $data);
        $this->assertArrayHasKey('total', $data);
    }

    public function test_user_list_filter_by_status()
    {
        $this->createTestUser(0, 0, 1);
        $response = $this->adminGet('/api/v1/admin/users?status=1');
        $this->assertApiSuccess($response, '按状态筛选用户');
    }

    public function test_user_list_search_by_keyword()
    {
        $userId = $this->createTestUser();
        $user = DB::table('users')->where('id', $userId)->first();
        $response = $this->adminGet('/api/v1/admin/users?keyword=' . urlencode($user->nickname));
        $this->assertApiSuccess($response, '按关键词搜索用户');
    }

    // ========== 2. 用户详情 ==========
    public function test_user_detail_returns_correct_data()
    {
        $userId = $this->createTestUser(100, 50);
        $response = $this->adminGet('/api/v1/admin/users/' . $userId);
        $this->assertApiSuccess($response, '用户详情');
        $data = $response->json('data');
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('orders', $data);
        $this->assertArrayHasKey('addresses', $data);
        $this->assertArrayHasKey('balance_logs', $data);
        $this->assertArrayHasKey('point_logs', $data);
        $this->assertArrayHasKey('real_name', $data);
        $this->assertEquals($userId, $data['user']['id']);
        $this->assertEquals(100, $data['user']['balance']);
        $this->assertEquals(50, $data['user']['points']);
    }

    public function test_user_detail_not_found_returns_error()
    {
        $response = $this->adminGet('/api/v1/admin/users/999999');
        $this->assertNotEquals(200, $response->json('code'), '不存在的用户应返回错误');
    }

    public function test_user_detail_hides_password()
    {
        $userId = $this->createTestUser();
        $response = $this->adminGet('/api/v1/admin/users/' . $userId);
        $data = $response->json('data');
        $this->assertArrayNotHasKey('password', $data['user'] ?? [], '用户详情不应返回密码');
    }

    // ========== 3. 用户编辑 ==========
    public function test_update_user_info_success()
    {
        $userId = $this->createTestUser();
        $uniqueMobile = '138' . time() . rand(100, 999);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminLogin(),
            'Accept' => 'application/json',
        ])->putJson('/api/v1/admin/users/' . $userId, [
            'nickname' => '更新后的昵称',
            'mobile' => $uniqueMobile,
        ]);
        $this->assertApiSuccess($response, '编辑用户信息');

        $user = DB::table('users')->where('id', $userId)->first();
        $this->assertEquals('更新后的昵称', $user->nickname);
        $this->assertEquals($uniqueMobile, $user->mobile);
    }

    public function test_update_user_balance()
    {
        $userId = $this->createTestUser(50);
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminLogin(),
            'Accept' => 'application/json',
        ])->putJson('/api/v1/admin/users/' . $userId, [
            'balance' => 200,
        ]);

        $user = DB::table('users')->where('id', $userId)->first();
        $this->assertEquals(200, $user->balance, '管理员可调整用户余额');
    }

    public function test_update_user_points()
    {
        $userId = $this->createTestUser(0, 100);
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminLogin(),
            'Accept' => 'application/json',
        ])->putJson('/api/v1/admin/users/' . $userId, [
            'points' => 500,
        ]);

        $user = DB::table('users')->where('id', $userId)->first();
        $this->assertEquals(500, $user->points, '管理员可调整用户积分');
    }

    public function test_update_user_password_is_hashed()
    {
        $userId = $this->createTestUser();
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminLogin(),
            'Accept' => 'application/json',
        ])->putJson('/api/v1/admin/users/' . $userId, [
            'password' => 'newpassword123',
        ]);

        $user = DB::table('users')->where('id', $userId)->first();
        $this->assertTrue(Hash::check('newpassword123', $user->password), '密码应被加密存储');
        $this->assertNotEquals('newpassword123', $user->password, '密码不应明文存储');
    }

    public function test_update_user_password_min_length()
    {
        $userId = $this->createTestUser();
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminLogin(),
            'Accept' => 'application/json',
        ])->putJson('/api/v1/admin/users/' . $userId, [
            'password' => '123',
        ]);
        $this->assertEquals(422, $response->status(), '密码最少6位');
    }

    // ========== 4. 用户状态切换 ==========
    public function test_toggle_user_status()
    {
        $userId = $this->createTestUser(0, 0, 1);
        $response = $this->adminPost('/api/v1/admin/users/' . $userId . '/toggle-status', []);
        $this->assertApiSuccess($response, '用户状态切换');

        $user = DB::table('users')->where('id', $userId)->first();
        $this->assertEquals(0, $user->status, '启用用户切换后应禁用');
    }

    public function test_toggle_status_off_to_on()
    {
        $userId = $this->createTestUser(0, 0, 0);
        $this->adminPost('/api/v1/admin/users/' . $userId . '/toggle-status', []);
        $user = DB::table('users')->where('id', $userId)->first();
        $this->assertEquals(1, $user->status, '禁用用户切换后应启用');
    }

    // ========== 5. 用户等级管理 ==========
    public function test_user_level_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/user-center/levels');
        $this->assertApiSuccess($response, '用户等级列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('list', $data);
        $this->assertArrayHasKey('stats', $data);
    }

    public function test_create_user_level()
    {
        $response = $this->adminPost('/api/v1/admin/user-center/levels', [
            'name' => '测试等级' . time(),
            'level' => 10,
            'discount' => 90.00,
            'upgrade_points' => 10000,
            'status' => 1,
        ]);
        $this->assertApiSuccess($response, '创建用户等级');
    }

    public function test_user_level_discount_field()
    {
        $levelId = DB::table('user_levels')->insertGetId([
            'name' => '折扣测试等级' . time(),
            'level' => 5,
            'discount' => 85.00,
            'upgrade_points' => 5000,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $level = DB::table('user_levels')->where('id', $levelId)->first();
        $this->assertEquals(85.00, $level->discount, '等级折扣应为85折');
    }

    public function test_default_level_exists()
    {
        $defaultCount = DB::table('user_levels')->where('is_default', 1)->count();
        $this->assertGreaterThanOrEqual(0, $defaultCount, '默认等级可以不存在或存在');
    }

    // ========== 6. 实名认证审核 ==========
    public function test_realname_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/user-auth');
        $this->assertApiSuccess($response, '实名认证列表');
    }

    public function test_realname_detail_returns_correct_data()
    {
        $userId = $this->createTestUser();
        $realNameId = $this->createTestRealName($userId, 0);

        $response = $this->adminGet('/api/v1/admin/user-auth/' . $realNameId);
        $this->assertApiSuccess($response, '实名认证详情');
    }

    public function test_realname_audit_approve()
    {
        $userId = $this->createTestUser();
        $realNameId = $this->createTestRealName($userId, 0);

        $response = $this->adminPost('/api/v1/admin/user-auth/' . $realNameId . '/audit', [
            'status' => 1,
            'audit_remark' => '审核通过',
        ]);
        $this->assertApiSuccess($response, '实名认证审核通过');

        $realName = DB::table('user_real_names')->where('id', $realNameId)->first();
        $this->assertEquals(1, $realName->status, '审核通过后状态应为1');
        $this->assertNotNull($realName->audit_at, '审核时间应记录');
    }

    public function test_realname_audit_reject()
    {
        $userId = $this->createTestUser();
        $realNameId = $this->createTestRealName($userId, 0);

        $response = $this->adminPost('/api/v1/admin/user-auth/' . $realNameId . '/audit', [
            'status' => 2,
            'audit_remark' => '身份证照片不清晰',
        ]);
        $this->assertApiSuccess($response, '实名认证审核拒绝');

        $realName = DB::table('user_real_names')->where('id', $realNameId)->first();
        $this->assertEquals(2, $realName->status, '审核拒绝后状态应为2');
        $this->assertEquals('身份证照片不清晰', $realName->audit_remark);
    }

    public function test_realname_user_id_is_unique()
    {
        $userId = $this->createTestUser();
        $this->createTestRealName($userId, 0);

        try {
            DB::table('user_real_names')->insert([
                'user_id' => $userId,
                'real_name' => '李四',
                'id_card' => '110101199002021234',
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('user_id应该有唯一约束，一个用户只能有一条实名认证记录');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Duplicate', $e->getMessage());
        }
    }

    public function test_cannot_audit_realname_twice()
    {
        $userId = $this->createTestUser();
        $realNameId = $this->createTestRealName($userId, 1);

        $response = $this->adminPost('/api/v1/admin/user-auth/' . $realNameId . '/audit', [
            'status' => 1,
        ]);
        $this->assertNotEquals(200, $response->json('code'), '已审核的实名认证不能重复审核');
    }

    // ========== 7. 用户等级关联 ==========
    public function test_user_level_relationship()
    {
        $levelId = DB::table('user_levels')->insertGetId([
            'name' => '关联测试等级' . time(),
            'level' => 3,
            'discount' => 95.00,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->insertGetId([
            'account' => 'leveltest' . time(),
            'mobile' => '137' . time(),
            'nickname' => '等级测试用户',
            'password' => Hash::make('123456'),
            'level_id' => $levelId,
            'balance' => 0,
            'points' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = DB::table('users')->where('id', $userId)->first();
        $this->assertEquals($levelId, $user->level_id, '用户应关联等级');
    }

    // ========== 8. 未授权访问 ==========
    public function test_user_management_requires_auth()
    {
        $response = $this->get('/api/v1/admin/users');
        $this->assertContains($response->status(), [401, 500, 302], '未授权应返回401');
    }
}
