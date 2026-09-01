<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

echo "=== 创建超级管理员 ===\n";
$admin = DB::table('admins')->where('username', 'admin')->first();
if (!$admin) {
    DB::table('admins')->insert([
        'username' => 'admin',
        'password' => Hash::make('admin123'),
        'nickname' => '超级管理员',
        'role_id' => 1,
        'status' => 1,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);
    echo "管理员创建成功: admin / admin123\n";
} else {
    echo "管理员已存在\n";
}

echo "=== 创建管理员角色 ===\n";
$role = DB::table('admin_roles')->where('name', 'super_admin')->first();
if (!$role) {
    DB::table('admin_roles')->insert([
        'name' => 'super_admin',
        'description' => '超级管理员',
        'permissions' => json_encode(['*']),
        'status' => 1,
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
    ]);
    echo "角色创建成功\n";
}

echo "=== 插入系统配置 ===\n";
$configs = [
    ['group' => 'basic', 'key' => 'site_name', 'name' => '站点名称', 'value' => 'TLLOS 商城', 'type' => 'text'],
    ['group' => 'basic', 'key' => 'site_logo', 'name' => '站点Logo', 'value' => '', 'type' => 'image'],
    ['group' => 'basic', 'key' => 'site_icp', 'name' => 'ICP备案号', 'value' => '', 'type' => 'text'],
    ['group' => 'basic', 'key' => 'contact_phone', 'name' => '客服电话', 'value' => '400-000-0000', 'type' => 'text'],
    ['group' => 'basic', 'key' => 'contact_email', 'name' => '客服邮箱', 'value' => 'service@tllos.com', 'type' => 'text'],
    ['group' => 'trade', 'key' => 'auto_cancel_minutes', 'name' => '订单自动取消时间(分钟)', 'value' => '30', 'type' => 'number'],
    ['group' => 'trade', 'key' => 'auto_confirm_days', 'name' => '自动确认收货天数', 'value' => '7', 'type' => 'number'],
    ['group' => 'trade', 'key' => 'default_shipping_fee', 'name' => '默认运费', 'value' => '0', 'type' => 'number'],
    ['group' => 'trade', 'key' => 'free_shipping_amount', 'name' => '满额包邮金额', 'value' => '99', 'type' => 'number'],
    ['group' => 'user', 'key' => 'register_reward_points', 'name' => '注册赠送积分', 'value' => '100', 'type' => 'number'],
    ['group' => 'user', 'key' => 'points_rate', 'name' => '积分抵扣比例', 'value' => '100', 'type' => 'number'],
];
$count = 0;
foreach ($configs as $c) {
    if (!DB::table('system_configs')->where('key', $c['key'])->first()) {
        DB::table('system_configs')->insert(array_merge($c, ['sort' => 0, 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]));
        $count++;
    }
}
echo "系统配置插入: {$count} 项\n";

echo "=== 插入商品分类 ===\n";
$categories = ['手机数码', '电脑办公', '家用电器', '服饰鞋包', '美妆个护', '食品生鲜', '母婴玩具', '运动户外'];
$count = 0;
foreach ($categories as $i => $name) {
    if (!DB::table('product_categories')->where('name', $name)->first()) {
        DB::table('product_categories')->insert([
            'parent_id' => 0, 'name' => $name, 'level' => 1, 'sort' => $i + 1,
            'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);
        $count++;
    }
}
echo "商品分类插入: {$count} 个\n";

echo "=== 插入快递公司 ===\n";
$expresses = [
    ['顺丰速运', 'SF', '95338'], ['圆通速递', 'YTO', '95554'],
    ['中通快递', 'ZTO', '95311'], ['韵达快递', 'YD', '95546'],
    ['申通快递', 'STO', '95543'], ['百世快递', 'HTKY', '95320'],
    ['邮政EMS', 'EMS', '11183'], ['京东物流', 'JD', '950616'],
];
$count = 0;
foreach ($expresses as $e) {
    if (!DB::table('express_companies')->where('code', $e[1])->first()) {
        DB::table('express_companies')->insert([
            'name' => $e[0], 'code' => $e[1], 'phone' => $e[2],
            'sort' => 0, 'status' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);
        $count++;
    }
}
echo "快递公司插入: {$count} 个\n";

echo "=== 插入轮播图 ===\n";
$banners = [
    ['新品首发', 'banner1'], ['限时秒杀', 'banner2'], ['品牌特惠', 'banner3'],
];
$count = 0;
foreach ($banners as $b) {
    if (!DB::table('banners')->where('title', $b[0])->first()) {
        DB::table('banners')->insert([
            'position' => 'home', 'title' => $b[0],
            'image' => "https://picsum.photos/seed/{$b[1]}/750/300",
            'link_type' => 'none', 'sort' => $count + 1, 'status' => 1,
            'created_at' => Carbon::now(), 'updated_at' => Carbon::now(),
        ]);
        $count++;
    }
}
echo "轮播图插入: {$count} 张\n";

echo "\n=== 基础数据初始化完成 ===\n";
echo "管理员账号: admin / admin123\n";
