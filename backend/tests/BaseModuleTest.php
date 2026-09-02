<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class BaseModuleTest extends BaseTestCase
{
    protected $adminToken;
    protected $userToken;

    protected function adminLogin()
    {
        if ($this->adminToken) return $this->adminToken;
        $response = $this->postJson('/api/v1/admin/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        $this->adminToken = $response->json('data.token');
        $this->assertNotNull($this->adminToken, '管理员登录失败');
        return $this->adminToken;
    }

    protected function userLogin()
    {
        if ($this->userToken) return $this->userToken;
        $response = $this->postJson('/api/v1/auth/login', [
            'account' => '133001330001',
            'password' => '123456',
        ]);
        $this->userToken = $response->json('data.token');
        $this->assertNotNull($this->userToken, '用户登录失败');
        return $this->userToken;
    }

    protected function adminGet($uri, $params = [])
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminLogin(),
            'Accept' => 'application/json',
        ])->get($uri, $params);
    }

    protected function adminPost($uri, $data = [])
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminLogin(),
            'Accept' => 'application/json',
        ])->postJson($uri, $data);
    }

    protected function userGet($uri, $params = [])
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userLogin(),
            'Accept' => 'application/json',
        ])->get($uri, $params);
    }

    protected function userPost($uri, $data = [])
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userLogin(),
            'Accept' => 'application/json',
        ])->postJson($uri, $data);
    }

    protected function assertApiSuccess($response, $message = '')
    {
        $status = $response->status();
        $this->assertContains($status, [200, 201], "{$message} HTTP状态码异常: {$status}");
        $code = $response->json('code');
        $this->assertEquals(200, $code, "{$message} 业务code异常: {$code}, 响应: " . substr($response->getContent(), 0, 300));
    }

    protected function assertApiList($response, $message = '')
    {
        $this->assertApiSuccess($response, $message);
        $data = $response->json('data');
        $this->assertIsArray($data, "{$message} data不是数组");
    }
}
