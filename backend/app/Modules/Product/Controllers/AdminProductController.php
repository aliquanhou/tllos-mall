<?php
namespace App\Modules\Product\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductCategory;
use Illuminate\Http\Request;

class AdminProductController extends BaseController
{
    public function index(Request $request)
    {
        $query = Product::with('category:id,name');
        if ($request->keyword) $query->where('name', 'like', "%{$request->keyword}%");
        if ($request->category_id) $query->where('category_id', $request->category_id);
        if ($request->status !== null) $query->where('status', $request->status);
        if ($request->merchant_id) $query->where('merchant_id', $request->merchant_id);

        $list = $query->orderBy('id', 'desc')->paginate($request->limit ?: 20);
        return $this->success(['list' => $list->items(), 'total' => $list->total()]);
    }

    public function show($id)
    {
        $product = Product::with(['category:id,name', 'skus'])->find($id);
        if (!$product) return $this->error('商品不存在', 404);
        return $this->success($product);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'category_id' => 'required|integer',
            'price' => 'required|numeric|min:0',
            'main_image' => 'required|string',
            'stock' => 'required|integer|min:0',
        ]);

        $data = $request->only([
            'merchant_id', 'category_id', 'brand_id', 'name', 'subtitle',
            'main_image', 'images', 'description', 'price', 'market_price',
            'cost_price', 'stock', 'is_sku', 'unit', 'weight',
            'is_free_shipping', 'shipping_fee', 'is_new', 'is_hot', 'is_recommend', 'status',
        ]);
        $data['images'] = $request->images ? json_encode($request->images) : null;

        $product = Product::create($data);
        return $this->success($product, '创建成功');
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) return $this->error('商品不存在', 404);

        $data = $request->only([
            'category_id', 'brand_id', 'name', 'subtitle', 'main_image', 'images',
            'description', 'price', 'market_price', 'cost_price', 'stock', 'is_sku',
            'unit', 'weight', 'is_free_shipping', 'shipping_fee', 'is_new', 'is_hot',
            'is_recommend', 'status',
        ]);
        if ($request->has('images')) $data['images'] = json_encode($request->images);

        $product->update($data);
        return $this->success($product, '更新成功');
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) return $this->error('商品不存在', 404);
        $product->delete();
        return $this->success(null, '删除成功');
    }

    public function toggleStatus($id)
    {
        $product = Product::find($id);
        if (!$product) return $this->error('商品不存在', 404);
        $product->status = $product->status == 1 ? 0 : 1;
        $product->save();
        return $this->success(['status' => $product->status], '操作成功');
    }

    public function batchUpdate(Request $request)
    {
        $ids = $request->input("ids", []);
        $data = $request->except("ids");
        if (empty($ids)) return $this->error("请选择商品");
        \App\Modules\Product\Models\Product::whereIn("id", $ids)->update($data);
        return $this->success(["updated" => count($ids)]);
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->input("ids", []);
        if (empty($ids)) return $this->error("请选择商品");
        \App\Modules\Product\Models\Product::whereIn("id", $ids)->delete();
        return $this->success(["deleted" => count($ids)]);
    }
}
