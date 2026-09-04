<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestDatabaseSeeder extends Seeder
{
    public function run()
    {
        // 管理员
        DB::table('admins')->updateOrInsert(['id' => 1], [
            'username' => 'admin',
            'password' => Hash::make('admin123'),
            'nickname' => '超级管理员',
            'role_id' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 用户
        DB::table('users')->updateOrInsert(['id' => 1], [
            'mobile' => '133001330001',
            'password' => Hash::make('123456'),
            'nickname' => '测试用户1',
            'name' => '测试用户1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        echo "测试数据已填充\n";
    }
}
