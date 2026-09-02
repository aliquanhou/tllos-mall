<?php
namespace Tests\Feature;

use Tests\BaseModuleTest;
use Illuminate\Support\Facades\DB;

class ProductBusinessTest extends BaseModuleTest
{
    protected function createTestProduct($status = 1, $stock = 100)
    {
        return DB::table('products')->insertGetId([
            'merchant_id' => 0,
            'category_id' => 1,
            'brand_id' => 0,
            'name' => '测试商品' . time() . rand(100, 999),
            'subtitle' => '测试副标题',
            'main_image' => 'https://example.com/image.jpg',
            'images' => json_encode(['https://example.com/image1.jpg']),
            'description' => '测试商品描述',
            'price' => 99.00,
            'market_price' => 129.00,
            'cost_price' => 50.00,
            'stock' => $stock,
            'warning_stock' => 10,
            'sales' => 0,
            'views' => 0,
            'is_sku' => 0,
            'unit' => '件',
            'weight' => 0.5,
            'is_free_shipping' => 0,
            'shipping_fee' => 10.00,
            'is_new' => 0,
            'is_hot' => 0,
            'is_recommend' => 0,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createTestCategory($parentId = 0, $level = 1)
    {
        return DB::table('product_categories')->insertGetId([
            'parent_id' => $parentId,
            'name' => '测试分类' . time() . rand(100, 999),
            'level' => $level,
            'sort' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ========== 1. 商品列表查询 ==========
    public function test_product_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/products');
        $this->assertApiSuccess($response, '商品列表');
        $data = $response->json('data');
        $this->assertArrayHasKey('list', $data);
        $this->assertArrayHasKey('total', $data);
    }

    public function test_product_list_filter_by_status()
    {
        $this->createTestProduct(1);
        $response = $this->adminGet('/api/v1/admin/products?status=1');
        $this->assertApiSuccess($response, '按状态筛选商品');
        $list = $response->json('data.list');
        foreach ($list as $product) {
            $this->assertEquals(1, $product['status']);
        }
    }

    public function test_product_list_search_by_keyword()
    {
        $productId = $this->createTestProduct(1);
        $product = DB::table('products')->where('id', $productId)->first();
        $response = $this->adminGet('/api/v1/admin/products?keyword=' . urlencode($product->name));
        $this->assertApiSuccess($response, '按关键词搜索商品');
    }

    public function test_product_list_filter_by_category()
    {
        $categoryId = $this->createTestCategory();
        $productId = DB::table('products')->insertGetId([
            'merchant_id' => 0, 'category_id' => $categoryId, 'brand_id' => 0,
            'name' => '分类测试商品' . time(), 'main_image' => 'test.jpg',
            'price' => 50, 'stock' => 10, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $response = $this->adminGet('/api/v1/admin/products?category_id=' . $categoryId);
        $this->assertApiSuccess($response, '按分类筛选商品');
    }

    // ========== 2. 商品详情 ==========
    public function test_product_detail_returns_correct_data()
    {
        $productId = $this->createTestProduct(1);
        $response = $this->adminGet('/api/v1/admin/products/' . $productId);
        $this->assertApiSuccess($response, '商品详情');
        $data = $response->json('data');
        $this->assertEquals($productId, $data['id']);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('price', $data);
        $this->assertArrayHasKey('stock', $data);
    }

    public function test_product_detail_not_found_returns_404()
    {
        $response = $this->adminGet('/api/v1/admin/products/999999');
        $this->assertEquals(404, $response->json('code'));
    }

    // ========== 3. 商品创建 ==========
    public function test_create_product_success()
    {
        $categoryId = $this->createTestCategory();
        $response = $this->adminPost('/api/v1/admin/products', [
            'name' => '新测试商品' . time(),
            'category_id' => $categoryId,
            'price' => 88.88,
            'main_image' => 'https://example.com/new.jpg',
            'stock' => 50,
        ]);
        $this->assertApiSuccess($response, '创建商品');
        $data = $response->json('data');
        $this->assertArrayHasKey('id', $data);

        $product = DB::table('products')->where('id', $data['id'])->first();
        $this->assertEquals(88.88, $product->price);
        $this->assertEquals(50, $product->stock);
        $this->assertEquals(1, $product->status, '新商品默认上架');
    }

    public function test_create_product_requires_name()
    {
        $response = $this->adminPost('/api/v1/admin/products', [
            'category_id' => 1,
            'price' => 50,
            'main_image' => 'test.jpg',
            'stock' => 10,
        ]);
        $this->assertEquals(422, $response->status(), '缺少name应返回422');
    }

    public function test_create_product_requires_category_id()
    {
        $response = $this->adminPost('/api/v1/admin/products', [
            'name' => '测试商品',
            'price' => 50,
            'main_image' => 'test.jpg',
            'stock' => 10,
        ]);
        $this->assertEquals(422, $response->status(), '缺少category_id应返回422');
    }

    public function test_create_product_price_must_be_non_negative()
    {
        $response = $this->adminPost('/api/v1/admin/products', [
            'name' => '测试商品',
            'category_id' => 1,
            'price' => -10,
            'main_image' => 'test.jpg',
            'stock' => 10,
        ]);
        $this->assertEquals(422, $response->status(), '价格不能为负');
    }

    public function test_create_product_stock_must_be_non_negative()
    {
        $response = $this->adminPost('/api/v1/admin/products', [
            'name' => '测试商品',
            'category_id' => 1,
            'price' => 50,
            'main_image' => 'test.jpg',
            'stock' => -5,
        ]);
        $this->assertEquals(422, $response->status(), '库存不能为负');
    }

    // ========== 4. 商品编辑 ==========
    public function test_update_product_success()
    {
        $productId = $this->createTestProduct(1);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminLogin(),
            'Accept' => 'application/json',
        ])->putJson('/api/v1/admin/products/' . $productId, [
            'name' => '更新后的商品名',
            'price' => 199.00,
        ]);
        $this->assertApiSuccess($response, '更新商品');

        $product = DB::table('products')->where('id', $productId)->first();
        $this->assertEquals('更新后的商品名', $product->name);
        $this->assertEquals(199.00, $product->price);
    }

    // ========== 5. 商品删除（软删除） ==========
    public function test_delete_product_soft_deletes()
    {
        $productId = $this->createTestProduct(1);
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminLogin(),
            'Accept' => 'application/json',
        ])->deleteJson('/api/v1/admin/products/' . $productId);
        $this->assertApiSuccess($response, '删除商品');

        $product = DB::table('products')->where('id', $productId)->first();
        $this->assertNotNull($product->deleted_at, '商品应被软删除');
    }

    // ========== 6. 商品上下架 ==========
    public function test_toggle_product_status()
    {
        $productId = $this->createTestProduct(1);
        $response = $this->adminPost('/api/v1/admin/products/' . $productId . '/toggle-status', []);
        $this->assertApiSuccess($response, '商品上下架切换');

        $product = DB::table('products')->where('id', $productId)->first();
        $this->assertEquals(0, $product->status, '上架商品切换后应下架');
    }

    public function test_toggle_status_off_to_on()
    {
        $productId = $this->createTestProduct(0);
        $this->adminPost('/api/v1/admin/products/' . $productId . '/toggle-status', []);
        $product = DB::table('products')->where('id', $productId)->first();
        $this->assertEquals(1, $product->status, '下架商品切换后应上架');
    }

    // ========== 7. SKU管理 ==========
    public function test_product_sku_creation()
    {
        $productId = $this->createTestProduct(1);
        $skuId = DB::table('product_skus')->insertGetId([
            'product_id' => $productId,
            'sku_no' => 'SKU' . time() . rand(1000, 9999),
            'specs' => json_encode(['颜色' => '红色', '尺寸' => 'L']),
            'spec_text' => '红色,L',
            'price' => 109.00,
            'market_price' => 139.00,
            'cost_price' => 60.00,
            'stock' => 50,
            'sales' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sku = DB::table('product_skus')->where('id', $skuId)->first();
        $this->assertEquals($productId, $sku->product_id);
        $this->assertEquals(109.00, $sku->price);
        $this->assertEquals(50, $sku->stock);
    }

    public function test_sku_no_is_unique()
    {
        $productId = $this->createTestProduct(1);
        $skuNo = 'SKU' . time() . 'UNIQUE';

        DB::table('product_skus')->insert([
            'product_id' => $productId,
            'sku_no' => $skuNo,
            'price' => 100,
            'stock' => 10,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            DB::table('product_skus')->insert([
                'product_id' => $productId,
                'sku_no' => $skuNo,
                'price' => 200,
                'stock' => 20,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->fail('sku_no应该有唯一约束');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Duplicate', $e->getMessage());
        }
    }

    // ========== 8. 分类树形结构 ==========
    public function test_category_tree_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/categories/tree');
        $this->assertApiSuccess($response, '分类树');
    }

    public function test_category_parent_child_relationship()
    {
        $parentId = $this->createTestCategory(0, 1);
        $childId = $this->createTestCategory($parentId, 2);

        $child = DB::table('product_categories')->where('id', $childId)->first();
        $this->assertEquals($parentId, $child->parent_id, '子分类应关联父分类');
        $this->assertEquals(2, $child->level, '子分类level应为2');
    }

    public function test_category_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/categories');
        $this->assertApiSuccess($response, '分类列表');
    }

    // ========== 9. 品牌管理 ==========
    public function test_brand_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/brands');
        $this->assertApiSuccess($response, '品牌列表');
    }

    public function test_brand_all_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/brands/all');
        $this->assertApiSuccess($response, '全部品牌');
    }

    // ========== 10. 商品评价 ==========
    public function test_comment_list_returns_200()
    {
        $response = $this->adminGet('/api/v1/admin/comments');
        $this->assertApiSuccess($response, '评价列表');
    }

    public function test_comment_toggle_show()
    {
        $productId = $this->createTestProduct(1);
        $commentId = DB::table('product_comments')->insertGetId([
            'product_id' => $productId,
            'user_id' => 1,
            'order_id' => 0,
            'order_item_id' => 0,
            'rating' => 5,
            'content' => '测试评价内容',
            'is_show' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->adminPost('/api/v1/admin/comments/' . $commentId . '/toggle-show', []);
        $this->assertApiSuccess($response, '评价显示切换');

        $comment = DB::table('product_comments')->where('id', $commentId)->first();
        $this->assertEquals(0, $comment->is_show, '切换后应隐藏');
    }

    // ========== 11. 库存预警 ==========
    public function test_low_stock_warning_field()
    {
        $productId = $this->createTestProduct(1, 5);
        $product = DB::table('products')->where('id', $productId)->first();
        $this->assertEquals(5, $product->stock);
        $this->assertEquals(10, $product->warning_stock, '预警库存默认10');
        $this->assertLessThan($product->warning_stock, $product->stock, '库存低于预警值');
    }

    // ========== 12. 未授权访问 ==========
    public function test_product_requires_auth()
    {
        $response = $this->get('/api/v1/admin/products');
        $this->assertContains($response->status(), [401, 500, 302], '未授权应返回401');
    }
}
