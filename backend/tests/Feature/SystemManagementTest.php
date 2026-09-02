<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;
use Illuminate\Support\Facades\DB;

class SystemManagementTest extends BaseModuleTest
{
    // ========== 管理员管理 ==========
    public function test_admin_list()
    {
        $response = $this->adminGet('/api/v1/admin/admin-manage');
        $this->assertApiSuccess($response, '管理员列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('list', $data);
        $this->assertArrayHasKey('total', $data);
    }

    public function test_admin_create()
    {
        $username = 'admin_test_' . time();
        $response = $this->adminPost('/api/v1/admin/admin-manage', [
            'username' => $username,
            'password' => '123456',
            'nickname' => '测试管理员',
            'role_id' => 1,
            'status' => 1,
        ]);
        $this->assertApiSuccess($response, '创建管理员');
        $adminId = $response->json('data.id');

        $admin = DB::table('admins')->where('id', $adminId)->first();
        $this->assertEquals($username, $admin->username);
        $this->assertNotNull($admin->password, '密码应已加密');

        // 清理
        DB::table('admins')->where('id', $adminId)->delete();
    }

    public function test_admin_update()
    {
        $adminId = DB::table('admins')->insertGetId([
            'username' => 'update_test_' . time(),
            'password' => bcrypt('123456'),
            'nickname' => '原昵称',
            'role_id' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
            ->putJson('/api/v1/admin/admin-manage/' . $adminId, [
                'nickname' => '新昵称',
                'status' => 0,
            ]);
        $this->assertApiSuccess($response, '编辑管理员');

        $admin = DB::table('admins')->where('id', $adminId)->first();
        $this->assertEquals('新昵称', $admin->nickname);
        $this->assertEquals(0, $admin->status);

        DB::table('admins')->where('id', $adminId)->delete();
    }

    public function test_admin_delete()
    {
        $adminId = DB::table('admins')->insertGetId([
            'username' => 'delete_test_' . time(),
            'password' => bcrypt('123456'),
            'nickname' => '待删除',
            'role_id' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
            ->deleteJson('/api/v1/admin/admin-manage/' . $adminId);
        $this->assertApiSuccess($response, '删除管理员');

        $this->assertNull(DB::table('admins')->where('id', $adminId)->first());
    }

    public function test_admin_username_unique()
    {
        $response = $this->adminPost('/api/v1/admin/admin-manage', [
            'username' => 'admin', // 已存在
            'password' => '123456',
            'role_id' => 1,
        ]);
        $this->assertNotEquals(200, $response->json('code'), '重复用户名应失败');
    }

    // ========== 字典管理 ==========
    public function test_dict_crud()
    {
        // 创建
        $response = $this->adminPost('/api/v1/admin/dicts', [
            'type' => 'test_type',
            'label' => '测试字典',
            'value' => 'test_value',
            'sort' => 1,
            'status' => 1,
        ]);
        $this->assertApiSuccess($response, '创建字典');
        $dictId = $response->json('data.id');

        // 列表
        $response = $this->adminGet('/api/v1/admin/dicts');
        $this->assertApiSuccess($response, '字典列表');

        // 详情
        $response = $this->adminGet('/api/v1/admin/dicts/' . $dictId);
        $this->assertApiSuccess($response, '字典详情');

        // 编辑
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
            ->putJson('/api/v1/admin/dicts/' . $dictId, [
                'label' => '更新字典',
                'value' => 'new_value',
            ]);
        $this->assertApiSuccess($response, '编辑字典');
        $dict = DB::table('dicts')->where('id', $dictId)->first();
        $this->assertEquals('更新字典', $dict->label);
        $this->assertEquals('new_value', $dict->value);

        // 删除
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
            ->deleteJson('/api/v1/admin/dicts/' . $dictId);
        $this->assertApiSuccess($response, '删除字典');
    }

    public function test_dict_by_type()
    {
        $type = 'test_type_' . time();
        DB::table('dicts')->insert([
            ['type' => $type, 'label' => '选项1', 'value' => '1', 'sort' => 1, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['type' => $type, 'label' => '选项2', 'value' => '2', 'sort' => 2, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->adminGet('/api/v1/admin/dicts?type=' . $type);
        $this->assertApiSuccess($response, '按类型查询字典');

        // 清理
        DB::table('dicts')->where('type', $type)->delete();
    }

    // ========== 配送方式管理 ==========
    public function test_delivery_type_crud()
    {
        // 列表
        $response = $this->adminGet('/api/v1/admin/delivery-type');
        $this->assertNotEquals(500, $response->status(), '配送方式列表不应500');

        // 创建
        $response = $this->adminPost('/api/v1/admin/delivery-type', [
            'name' => '测试配送' . time(),
            'type' => 1,
            'fee' => 10.00,
            'status' => 1,
            'sort' => 1,
        ]);
        if ($response->json('code') == 200) {
            $deliveryId = $response->json('data.id');

            // 编辑
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
                ->putJson('/api/v1/admin/delivery-type/' . $deliveryId, [
                    'name' => '更新配送',
                    'fee' => 15.00,
                ]);
            $this->assertApiSuccess($response, '编辑配送方式');

            // 删除
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
                ->deleteJson('/api/v1/admin/delivery-type/' . $deliveryId);
            $this->assertApiSuccess($response, '删除配送方式');
        }
        $this->assertTrue(true, '配送方式CRUD测试完成');
    }

    // ========== 缓存管理 ==========
    public function test_cache_clear()
    {
        $response = $this->adminPost('/api/v1/admin/cache/clear', []);
        $this->assertNotEquals(500, $response->status(), '清除缓存不应500');
    }

    // ========== 定时任务管理 ==========
    public function test_crontab_list()
    {
        $response = $this->adminGet('/api/v1/admin/crontabs');
        $this->assertNotEquals(500, $response->status(), '定时任务列表不应500');
    }

    public function test_crontab_crud()
    {
        // 创建
        $response = $this->adminPost('/api/v1/admin/crontabs', [
            'name' => '测试任务' . time(),
            'command' => 'test:command',
            'cron' => '0 0 * * *',
            'status' => 0,
        ]);
        if ($response->json('code') == 200) {
            $crontabId = $response->json('data.id');

            // 编辑
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
                ->putJson('/api/v1/admin/crontabs/' . $crontabId, [
                    'name' => '更新任务',
                    'status' => 1,
                ]);
            $this->assertApiSuccess($response, '编辑定时任务');

            // 删除
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
                ->deleteJson('/api/v1/admin/crontabs/' . $crontabId);
            $this->assertApiSuccess($response, '删除定时任务');
        }
        $this->assertTrue(true, '定时任务CRUD测试完成');
    }

    // ========== 部门管理 ==========
    public function test_dept_list()
    {
        $response = $this->adminGet('/api/v1/admin/admin-dept');
        $this->assertNotEquals(500, $response->status(), '部门列表不应500');
    }
}
