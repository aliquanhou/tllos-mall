<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminRoleMenuSeeder extends Seeder
{
    public function run()
    {
        // 清空旧数据
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('admin_roles')->truncate();
        DB::table('admin_menus')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ========== 1. 创建菜单（按功能分组） ==========
        $menus = [
            // 一级菜单
            ['id' => 1, 'parent_id' => 0, 'name' => '仪表盘', 'path' => '/dashboard', 'component' => 'dashboard/index', 'icon' => 'dashboard', 'sort' => 1, 'status' => 1, 'permission' => 'dashboard:view'],
            ['id' => 2, 'parent_id' => 0, 'name' => '商品管理', 'path' => '/products', 'component' => '', 'icon' => 'product', 'sort' => 2, 'status' => 1, 'permission' => 'product:view'],
            ['id' => 3, 'parent_id' => 0, 'name' => '订单管理', 'path' => '/orders', 'component' => '', 'icon' => 'order', 'sort' => 3, 'status' => 1, 'permission' => 'order:view'],
            ['id' => 4, 'parent_id' => 0, 'name' => '售后服务', 'path' => '/after-sale', 'component' => '', 'icon' => 'service', 'sort' => 4, 'status' => 1, 'permission' => 'aftersale:view'],
            ['id' => 5, 'parent_id' => 0, 'name' => '用户管理', 'path' => '/users', 'component' => '', 'icon' => 'user', 'sort' => 5, 'status' => 1, 'permission' => 'user:view'],
            ['id' => 6, 'parent_id' => 0, 'name' => '商家管理', 'path' => '/merchants', 'component' => '', 'icon' => 'merchant', 'sort' => 6, 'status' => 1, 'permission' => 'merchant:view'],
            ['id' => 7, 'parent_id' => 0, 'name' => '营销管理', 'path' => '/marketing', 'component' => '', 'icon' => 'marketing', 'sort' => 7, 'status' => 1, 'permission' => 'marketing:view'],
            ['id' => 8, 'parent_id' => 0, 'name' => '装修管理', 'path' => '/decorate', 'component' => '', 'icon' => 'decorate', 'sort' => 8, 'status' => 1, 'permission' => 'decorate:view'],
            ['id' => 9, 'parent_id' => 0, 'name' => '财务管理', 'path' => '/finance', 'component' => '', 'icon' => 'finance', 'sort' => 9, 'status' => 1, 'permission' => 'finance:view'],
            ['id' => 10, 'parent_id' => 0, 'name' => '系统管理', 'path' => '/system', 'component' => '', 'icon' => 'system', 'sort' => 10, 'status' => 1, 'permission' => 'system:view'],
            ['id' => 11, 'parent_id' => 0, 'name' => '权限管理', 'path' => '/permission', 'component' => '', 'icon' => 'permission', 'sort' => 11, 'status' => 1, 'permission' => 'permission:view'],
            ['id' => 12, 'parent_id' => 0, 'name' => '应用管理', 'path' => '/application', 'component' => '', 'icon' => 'application', 'sort' => 12, 'status' => 1, 'permission' => 'application:view'],
            ['id' => 13, 'parent_id' => 0, 'name' => '物流管理', 'path' => '/logistics', 'component' => '', 'icon' => 'logistics', 'sort' => 13, 'status' => 1, 'permission' => 'logistics:view'],
            ['id' => 14, 'parent_id' => 0, 'name' => '内容管理', 'path' => '/content', 'component' => '', 'icon' => 'content', 'sort' => 14, 'status' => 1, 'permission' => 'content:view'],
            ['id' => 15, 'parent_id' => 0, 'name' => '分销管理', 'path' => '/distribute', 'component' => '', 'icon' => 'distribute', 'sort' => 15, 'status' => 1, 'permission' => 'distribute:view'],
            ['id' => 16, 'parent_id' => 0, 'name' => '发票管理', 'path' => '/invoices', 'component' => '', 'icon' => 'invoice', 'sort' => 16, 'status' => 1, 'permission' => 'invoice:view'],

            // 商品管理子菜单
            ['id' => 20, 'parent_id' => 2, 'name' => '商品列表', 'path' => '/products/list', 'component' => 'product/list', 'icon' => '', 'sort' => 1, 'status' => 1, 'permission' => 'product:list'],
            ['id' => 21, 'parent_id' => 2, 'name' => '商品分类', 'path' => '/products/categories', 'component' => 'product/categories', 'icon' => '', 'sort' => 2, 'status' => 1, 'permission' => 'product:category'],
            ['id' => 22, 'parent_id' => 2, 'name' => '品牌管理', 'path' => '/products/brands', 'component' => 'product/brands', 'icon' => '', 'sort' => 3, 'status' => 1, 'permission' => 'product:brand'],
            ['id' => 23, 'parent_id' => 2, 'name' => '库存预警', 'path' => '/products/stock-warnings', 'component' => 'product/stock-warnings', 'icon' => '', 'sort' => 4, 'status' => 1, 'permission' => 'product:stock'],

            // 订单管理子菜单
            ['id' => 30, 'parent_id' => 3, 'name' => '订单列表', 'path' => '/orders/list', 'component' => 'order/list', 'icon' => '', 'sort' => 1, 'status' => 1, 'permission' => 'order:list'],
            ['id' => 31, 'parent_id' => 3, 'name' => '退款管理', 'path' => '/orders/refunds', 'component' => 'order/refunds', 'icon' => '', 'sort' => 2, 'status' => 1, 'permission' => 'order:refund'],
            ['id' => 32, 'parent_id' => 3, 'name' => '订单设置', 'path' => '/orders/setting', 'component' => 'order/setting', 'icon' => '', 'sort' => 3, 'status' => 1, 'permission' => 'order:setting'],

            // 系统管理子菜单
            ['id' => 100, 'parent_id' => 10, 'name' => '系统设置', 'path' => '/system/settings', 'component' => 'system/settings', 'icon' => '', 'sort' => 1, 'status' => 1, 'permission' => 'system:setting'],
            ['id' => 101, 'parent_id' => 10, 'name' => '定时任务', 'path' => '/system/crontabs', 'component' => 'system/crontabs', 'icon' => '', 'sort' => 2, 'status' => 1, 'permission' => 'system:crontab'],
            ['id' => 102, 'parent_id' => 10, 'name' => '存储配置', 'path' => '/system/storage', 'component' => 'system/storage', 'icon' => '', 'sort' => 3, 'status' => 1, 'permission' => 'system:storage'],
            ['id' => 103, 'parent_id' => 10, 'name' => '短信配置', 'path' => '/system/sms', 'component' => 'system/sms', 'icon' => '', 'sort' => 4, 'status' => 1, 'permission' => 'system:sms'],
            ['id' => 104, 'parent_id' => 10, 'name' => '操作日志', 'path' => '/system/logs', 'component' => 'system/logs', 'icon' => '', 'sort' => 5, 'status' => 1, 'permission' => 'system:log'],
            ['id' => 105, 'parent_id' => 10, 'name' => '系统信息', 'path' => '/system/info', 'component' => 'system/info', 'icon' => '', 'sort' => 6, 'status' => 1, 'permission' => 'system:info'],

            // 权限管理子菜单
            ['id' => 110, 'parent_id' => 11, 'name' => '角色管理', 'path' => '/permission/roles', 'component' => 'permission/roles', 'icon' => '', 'sort' => 1, 'status' => 1, 'permission' => 'permission:role'],
            ['id' => 111, 'parent_id' => 11, 'name' => '菜单管理', 'path' => '/permission/menus', 'component' => 'permission/menus', 'icon' => '', 'sort' => 2, 'status' => 1, 'permission' => 'permission:menu'],
            ['id' => 112, 'parent_id' => 11, 'name' => '管理员管理', 'path' => '/permission/admins', 'component' => 'permission/admins', 'icon' => '', 'sort' => 3, 'status' => 1, 'permission' => 'permission:admin'],
        ];

        foreach ($menus as $menu) {
            DB::table('admin_menus')->insert($menu);
        }
        echo "Created " . count($menus) . " menus\n";

        // ========== 2. 创建角色 ==========
        $allPermissions = array_column($menus, 'permission');
        $allPermissionsJson = json_encode($allPermissions);

        // 运营专员权限：仪表盘、商品、订单、装修、营销、物流
        $operatorPermissions = [
            'dashboard:view',
            'product:view', 'product:list', 'product:category', 'product:brand', 'product:stock',
            'order:view', 'order:list', 'order:refund',
            'decorate:view',
            'marketing:view',
            'logistics:view',
            'aftersale:view',
        ];
        $operatorPermissionsJson = json_encode($operatorPermissions);

        // 客服专员权限：仪表盘、售后、用户
        $supportPermissions = [
            'dashboard:view',
            'aftersale:view',
            'user:view',
            'order:view', 'order:list',
        ];
        $supportPermissionsJson = json_encode($supportPermissions);

        $roles = [
            ['id' => 1, 'name' => '超级管理员', 'description' => '拥有全部权限', 'permissions' => $allPermissionsJson, 'status' => 1],
            ['id' => 2, 'name' => '运营专员', 'description' => '负责商品、订单、装修、营销运营', 'permissions' => $operatorPermissionsJson, 'status' => 1],
            ['id' => 3, 'name' => '客服专员', 'description' => '负责售后、用户咨询', 'permissions' => $supportPermissionsJson, 'status' => 1],
        ];

        foreach ($roles as $role) {
            DB::table('admin_roles')->insert($role);
        }
        echo "Created " . count($roles) . " roles\n";

        // ========== 3. 创建测试管理员账号 ==========
        $admins = [
            ['id' => 2, 'username' => 'operator', 'password' => Hash::make('admin123'), 'nickname' => '运营专员', 'role_id' => 2, 'status' => 1],
            ['id' => 3, 'username' => 'support', 'password' => Hash::make('admin123'), 'nickname' => '客服专员', 'role_id' => 3, 'status' => 1],
        ];

        foreach ($admins as $admin) {
            DB::table('admins')->updateOrInsert(['id' => $admin['id']], $admin);
        }
        echo "Created " . count($admins) . " test admin accounts\n";

        echo "\n=== 权限数据初始化完成 ===\n";
        echo "角色: 超级管理员(全部权限), 运营专员(商品/订单/装修/营销), 客服专员(售后/用户)\n";
        echo "测试账号: operator/admin123, support/admin123\n";
    }
}
