<?php
namespace Tests\Feature;
use Tests\TestCase;

class AdminMenuSmokeTest extends TestCase
{
    public function test_all_admin_apis_smoke()
    {
        $loginResponse = $this->postJson('/api/v1/admin/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $token = $loginResponse->json('data.token');
        $this->assertNotNull($token, '登录失败，无法获取token');

        $routes = [
            '/api/v1/admin/system/config',
            '/api/v1/admin/system/express',
            '/api/v1/admin/system/logs',
            '/api/v1/admin/finance/income',
            '/api/v1/admin/finance/refund',
            '/api/v1/admin/finance/withdraw',
            '/api/v1/admin/finance/settlement',
            '/api/v1/admin/coupons',
            '/api/v1/admin/coupons/records',
            '/api/v1/admin/marketing/seckill',
            '/api/v1/admin/marketing/group',
            '/api/v1/admin/marketing/discount',
            '/api/v1/admin/distribute/overview',
            '/api/v1/admin/distribute/agents',
            '/api/v1/admin/distribute/levels',
            '/api/v1/admin/distribute/orders',
            '/api/v1/admin/distribute/goods',
            '/api/v1/admin/distribute/settings',
            '/api/v1/admin/application/deposit',
            '/api/v1/admin/application/material',
            '/api/v1/admin/application/article',
            '/api/v1/admin/application/article-categories',
            '/api/v1/admin/application/notice',
            '/api/v1/admin/application/kefu',
            '/api/v1/admin/application/collect',
            '/api/v1/admin/permission/role',
            '/api/v1/admin/permission/menu',
            '/api/v1/admin/permission/dept',
            '/api/v1/admin/products',
            '/api/v1/admin/categories',
            '/api/v1/admin/categories/tree',
            '/api/v1/admin/comments',
            '/api/v1/admin/orders',
            '/api/v1/admin/refunds',
            '/api/v1/admin/merchants',
            '/api/v1/admin/users',
            '/api/v1/admin/profile',
        ];

        $pass = 0;
        $fail = 0;
        $failures = [];

        foreach ($routes as $uri) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get($uri);

            $status = $response->status();
            if (in_array($status, [200, 201, 302, 401, 403])) {
                $pass++;
                echo "PASS {$uri} -> {$status}\n";
            } else {
                $fail++;
                $failures[] = "{$uri} -> {$status}: " . substr($response->getContent(), 0, 200);
                echo "FAIL {$uri} -> {$status}\n";
            }
        }

        echo "\n========================================\n";
        echo "API冒烟测试结果: {$pass} 通过, {$fail} 失败\n";
        echo "========================================\n";

        if ($fail > 0) {
            echo "\n失败详情:\n";
            foreach ($failures as $f) {
                echo "  - {$f}\n";
            }
        }

        $this->assertEquals(0, $fail, "有{$fail}个API冒烟测试失败");
    }
}
