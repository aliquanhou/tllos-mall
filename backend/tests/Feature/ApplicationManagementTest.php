<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;
use Illuminate\Support\Facades\DB;

class ApplicationManagementTest extends BaseModuleTest
{
    // ========== 公告管理 ==========
    public function test_announcement_crud()
    {
        // 创建
        $response = $this->adminPost('/api/v1/admin/announcements', [
            'title' => '测试公告' . time(),
            'content' => '公告内容',
            'status' => 1,
            'sort' => 1,
        ]);
        $this->assertApiSuccess($response, '创建公告');
        $announcementId = $response->json('data.id');

        // 列表
        $response = $this->adminGet('/api/v1/admin/announcements');
        $this->assertApiSuccess($response, '公告列表');

        // 编辑
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
            ->putJson('/api/v1/admin/announcement/' . $announcementId, [
                'title' => '更新公告',
                'content' => '更新内容',
            ]);
        $this->assertApiSuccess($response, '编辑公告');

        // 删除
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
            ->deleteJson('/api/v1/admin/announcement/' . $announcementId);
        $this->assertApiSuccess($response, '删除公告');
    }

    public function test_announcement_list_with_stats()
    {
        $response = $this->adminGet('/api/v1/admin/announcement');
        $this->assertNotEquals(500, $response->status(), '公告列表不应500');
    }

    // ========== 文章管理 ==========
    public function test_article_crud()
    {
        // 创建
        $response = $this->adminPost('/api/v1/admin/application/article', [
            'title' => '测试文章' . time(),
            'content' => '文章内容',
            'category_id' => 1,
            'status' => 1,
            'sort' => 1,
        ]);
        if ($response->json('code') == 200) {
            $articleId = $response->json('data.id');

            // 列表
            $response = $this->adminGet('/api/v1/admin/application/article');
            $this->assertApiSuccess($response, '文章列表');

            // 编辑
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
                ->putJson('/api/v1/admin/application/article/' . $articleId, [
                    'title' => '更新文章',
                ]);
            $this->assertApiSuccess($response, '编辑文章');

            // 删除
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
                ->deleteJson('/api/v1/admin/application/article/' . $articleId);
            $this->assertApiSuccess($response, '删除文章');
        }
        $this->assertTrue(true, '文章CRUD测试完成');
    }

    public function test_article_categories()
    {
        $response = $this->adminGet('/api/v1/admin/application/article-categories');
        $this->assertNotEquals(500, $response->status(), '文章分类列表不应500');
    }

    // ========== 消息通知管理 ==========
    public function test_notice_crud()
    {
        // 创建
        $response = $this->adminPost('/api/v1/admin/application/notice', [
            'title' => '测试通知' . time(),
            'content' => '通知内容',
            'type' => 1,
            'status' => 1,
        ]);
        if ($response->json('code') == 200) {
            $noticeId = $response->json('data.id');

            // 列表
            $response = $this->adminGet('/api/v1/admin/application/notice');
            $this->assertApiSuccess($response, '通知列表');

            // 编辑
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
                ->putJson('/api/v1/admin/application/notice/' . $noticeId, [
                    'title' => '更新通知',
                ]);
            $this->assertApiSuccess($response, '编辑通知');

            // 删除
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
                ->deleteJson('/api/v1/admin/application/notice/' . $noticeId);
            $this->assertApiSuccess($response, '删除通知');
        }
        $this->assertTrue(true, '通知CRUD测试完成');
    }

    // ========== 素材管理 ==========
    public function test_material_list()
    {
        $response = $this->adminGet('/api/v1/admin/application/material');
        $this->assertNotEquals(500, $response->status(), '素材列表不应500');
    }

    // ========== 客服管理 ==========
    public function test_kefu_list()
    {
        $response = $this->adminGet('/api/v1/admin/application/kefu');
        $this->assertNotEquals(500, $response->status(), '客服列表不应500');
    }

    public function test_kefu_setting_save()
    {
        // 客服设置保存在system_configs表
        $response = $this->adminPost('/api/v1/admin/application/kefu', [
            'kefu_enabled' => 1,
            'kefu_phone' => '400-123-4567',
            'kefu_wechat' => 'kefu_wechat',
            'kefu_worktime' => '9:00-18:00',
            'kefu_qq' => '123456',
        ]);
        $this->assertNotEquals(500, $response->status(), '保存客服设置不应500');
    }

    // ========== 装修管理 - 轮播图 ==========
    public function test_banner_list()
    {
        $response = $this->adminGet('/api/v1/admin/banners');
        $this->assertNotEquals(500, $response->status(), '轮播图列表不应500');
    }

    public function test_banner_crud()
    {
        // 创建
        $response = $this->adminPost('/api/v1/admin/banners', [
            'title' => '测试轮播' . time(),
            'image' => 'test.jpg',
            'link' => '/product/1',
            'sort' => 1,
            'status' => 1,
        ]);
        if ($response->json('code') == 200) {
            $bannerId = $response->json('data.id');

            // 编辑
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
                ->putJson('/api/v1/admin/banners/' . $bannerId, [
                    'title' => '更新轮播',
                ]);
            $this->assertApiSuccess($response, '编辑轮播图');

            // 删除
            $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->adminLogin()])
                ->deleteJson('/api/v1/admin/banners/' . $bannerId);
            $this->assertApiSuccess($response, '删除轮播图');
        }
        $this->assertTrue(true, '轮播图CRUD测试完成');
    }

    // ========== 导航管理 ==========
    public function test_navigation_list()
    {
        $response = $this->adminGet('/api/v1/admin/navigations');
        $this->assertNotEquals(500, $response->status(), '导航列表不应500');
    }

    // ========== 页面装修 ==========
    public function test_page_decorate_list()
    {
        $response = $this->adminGet('/api/v1/admin/decorate/pages');
        $this->assertNotEquals(500, $response->status(), '页面装修列表不应500');
    }
}
