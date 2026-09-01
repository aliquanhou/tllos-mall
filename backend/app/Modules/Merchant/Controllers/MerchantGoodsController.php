<?php
namespace App\Modules\Merchant\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MerchantGoodsController extends BaseController
{
    private function getShopId($request)
    {
        $shopId = DB::table('shops')->where('user_id', $request->user()->id)->value('id');
        return $shopId ?? 1;
    }

    private function decodeImages($goods)
    {
        if (!$goods) return null;
        if (is_string($goods->images ?? null)) {
            $decoded = json_decode($goods->images, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $goods->images = $decoded;
            } else {
                $goods->images = [];
            }
        }
        if (is_string($goods->sku_list ?? null)) {
            $decoded = json_decode($goods->sku_list, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $goods->sku_list = $decoded;
            }
        }
        return $goods;
    }

    public function lists(Request $request)
    {
        $shopId = $this->getShopId($request);
        $query = DB::table('products')->where('merchant_id', $shopId);

        if ($request->filled('keyword')) {
            $query->where('name', 'like', '%' . $request->keyword . '%');
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $total = $query->count();
        $list = $query->orderBy('id', 'desc')
            ->offset(($request->get('page', 1) - 1) * $request->get('limit', 20))
            ->limit($request->get('limit', 20))
            ->get()
            ->map(function ($item) {
                return $this->decodeImages($item);
            });

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function add(Request $request)
    {
        $shopId = $this->getShopId($request);

        $data = $request->only([
            'name', 'subtitle', 'category_id', 'brand_id', 'main_image',
            'price', 'market_price', 'cost_price', 'stock', 'weight',
            'description', 'video', 'is_hot', 'is_new', 'is_recommend',
            'is_free_shipping', 'shipping_fee', 'unit', 'status', 'is_sku'
        ]);

        $data['merchant_id'] = $shopId;
        $data['shop_id'] = $shopId;
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $data['sales'] = 0;
        $data['views'] = 0;

        // 处理附图
        $images = $request->input('images', []);
        if (is_array($images)) {
            $data['images'] = json_encode($images, JSON_UNESCAPED_UNICODE);
        }

        // 处理SKU
        $skuList = $request->input('sku_list', []);
        if (is_array($skuList) && count($skuList) > 0) {
            $data['sku_list'] = json_encode($skuList, JSON_UNESCAPED_UNICODE);
            $data['is_sku'] = 1;
        }

        $id = DB::table('products')->insertGetId($data);

        return $this->success(['id' => $id], '商品添加成功');
    }

    public function edit(Request $request, $id)
    {
        $shopId = $this->getShopId($request);

        $data = $request->only([
            'name', 'subtitle', 'category_id', 'brand_id', 'main_image',
            'price', 'market_price', 'cost_price', 'stock', 'weight',
            'description', 'video', 'is_hot', 'is_new', 'is_recommend',
            'is_free_shipping', 'shipping_fee', 'unit', 'status', 'is_sku'
        ]);

        $data['updated_at'] = now();

        // 处理附图
        if ($request->has('images')) {
            $images = $request->input('images', []);
            if (is_array($images)) {
                $data['images'] = json_encode($images, JSON_UNESCAPED_UNICODE);
            }
        }

        // 处理SKU
        if ($request->has('sku_list')) {
            $skuList = $request->input('sku_list', []);
            if (is_array($skuList) && count($skuList) > 0) {
                $data['sku_list'] = json_encode($skuList, JSON_UNESCAPED_UNICODE);
                $data['is_sku'] = 1;
            }
        }

        DB::table('products')->where('id', $id)->where('merchant_id', $shopId)->update($data);

        return $this->success(null, '商品修改成功');
    }

    public function delete(Request $request, $id)
    {
        $shopId = $this->getShopId($request);
        DB::table('products')->where('id', $id)->where('merchant_id', $shopId)->delete();
        return $this->success(null, '商品删除成功');
    }

    public function detail(Request $request, $id)
    {
        $shopId = $this->getShopId($request);
        $goods = DB::table('products')->where('id', $id)->where('merchant_id', $shopId)->first();
        $goods = $this->decodeImages($goods);
        return $this->success($goods);
    }

    public function batchUpdateStatus(Request $request)
    {
        $shopId = $this->getShopId($request);
        $ids = $request->input('ids', []);
        $status = $request->input('status', 0);

        if (empty($ids)) {
            return $this->error('请选择商品');
        }

        DB::table('products')->whereIn('id', $ids)->where('merchant_id', $shopId)->update(['status' => $status, 'updated_at' => now()]);

        return $this->success(null, '批量更新成功');
    }

    public function batchDelete(Request $request)
    {
        $shopId = $this->getShopId($request);
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return $this->error('请选择商品');
        }

        DB::table('products')->whereIn('id', $ids)->where('merchant_id', $shopId)->delete();

        return $this->success(null, '批量删除成功');
    }
}
