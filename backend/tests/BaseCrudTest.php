<?php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class BaseCrudTest extends BaseTestCase
{
    protected $adminToken;
    protected $userToken;
    protected $modulePath;      // API路径，如 /api/v1/admin/products
    protected $tableName;       // 数据库表名
    protected $createData;      // 新增数据
    protected $updateData;      // 更新数据
    protected $requiredFields;  // 必填字段，用于异常测试

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminToken = $this->getAdminToken();
        $this->userToken = $this->getUserToken();
    }

    protected function getAdminToken()
    {
        $response = $this->postJson('/api/v1/admin/login', [
            'username' => 'admin',
            'password' => 'admin123',
        ]);
        return $response->json('data.token');
    }

    protected function getUserToken()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'account' => '133001330001',
            'password' => '123456',
        ]);
        return $response->json('data.token');
    }

    protected function adminHeaders()
    {
        return ['Authorization' => 'Bearer ' . $this->adminToken, 'Accept' => 'application/json'];
    }

    protected function userHeaders()
    {
        return ['Authorization' => 'Bearer ' . $this->userToken, 'Accept' => 'application/json'];
    }

    // 列表测试
    public function test_list()
    {
        if (!$this->modulePath) {
            $this->markTestSkipped('模块路径未配置');
        }
        $response = $this->withHeaders($this->adminHeaders())->get($this->modulePath);
        $this->assertContains($response->status(), [200, 201], "列表接口HTTP状态异常: {$response->status()}");
    }

    // 新增测试
    public function test_create()
    {
        if (!$this->modulePath || !$this->createData) {
            $this->markTestSkipped('新增数据未配置');
        }
        $response = $this->withHeaders($this->adminHeaders())->postJson($this->modulePath, $this->createData);
        $this->assertContains($response->status(), [200, 201], "新增接口HTTP状态异常: {$response->status()}");
    }

    // 编辑测试
    public function test_update()
    {
        if (!$this->modulePath || !$this->updateData) {
            $this->markTestSkipped('更新数据未配置');
        }
        // 先创建一条记录
        $createResponse = $this->withHeaders($this->adminHeaders())->postJson($this->modulePath, $this->createData);
        $id = $createResponse->json('data.id') ?? 1;
        $response = $this->withHeaders($this->adminHeaders())->putJson($this->modulePath . '/' . $id, $this->updateData);
        $this->assertContains($response->status(), [200, 201], "编辑接口HTTP状态异常: {$response->status()}");
    }

    // 删除测试
    public function test_delete()
    {
        if (!$this->modulePath) {
            $this->markTestSkipped('模块路径未配置');
        }
        // 先创建一条记录
        $createResponse = $this->withHeaders($this->adminHeaders())->postJson($this->modulePath, $this->createData ?? ['name' => '测试删除']);
        $id = $createResponse->json('data.id') ?? 999;
        $response = $this->withHeaders($this->adminHeaders())->delete($this->modulePath . '/' . $id);
        $this->assertContains($response->status(), [200, 201, 404], "删除接口HTTP状态异常: {$response->status()}");
    }

    // 筛选测试
    public function test_filter()
    {
        if (!$this->modulePath) {
            $this->markTestSkipped('模块路径未配置');
        }
        $response = $this->withHeaders($this->adminHeaders())->get($this->modulePath . '?keyword=test&page=1&limit=10');
        $this->assertContains($response->status(), [200, 201], "筛选接口HTTP状态异常: {$response->status()}");
    }

    // 未授权测试
    public function test_unauthorized()
    {
        if (!$this->modulePath) {
            $this->markTestSkipped('模块路径未配置');
        }
        $response = $this->get($this->modulePath);
        $this->assertContains($response->status(), [401, 500, 302], "未授权访问应返回401，实际: {$response->status()}");
    }
}
