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
        if (empty($ids)) return $this->error("请选择商品");

        $price = $request->input("price");
        $discount = $request->input("discount");
        $updateSku = $request->input("update_sku", 0);
        $data = $request->except(["ids", "price", "discount", "update_sku"]);

        // 批量改价逻辑
        if ($price !== null || $discount !== null) {
            $products = \App\Modules\Product\Models\Product::whereIn("id", $ids)->get();
            foreach ($products as $product) {
                $newPrice = $price !== null ? floatval($price) : round($product->price * floatval($discount) / 10, 2);
                $product->price = $newPrice;
                // 市场价也按比例调整（如果有折扣）
                if ($discount !== null && $product->market_price) {
                    $product->market_price = round($product->market_price * floatval($discount) / 10, 2);
                }
                $product->save();

                // 同步更新SKU价格
                if ($updateSku) {
                    $skus = \App\Modules\Product\Models\ProductSku::where("product_id", $product->id)->get();
                    foreach ($skus as $sku) {
                        if ($price !== null) {
                            $sku->price = $newPrice;
                        } else {
                            $sku->price = round($sku->price * floatval($discount) / 10, 2);
                        }
                        $sku->save();
                    }
                }
            }
        }

        // 更新其他字段（如status）
        if (!empty($data)) {
            \App\Modules\Product\Models\Product::whereIn("id", $ids)->update($data);
        }

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
