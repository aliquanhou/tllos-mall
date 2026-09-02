<?php
namespace App\Modules\Product\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\ProductCategory;
use App\Modules\Product\Models\ProductSku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseController
{
    public function index(Request $request)
    {
        $query = Product::where('status', 1);
        if ($request->category_id) $query->where('category_id', $request->category_id);
        if ($request->keyword) $query->where('name', 'like', "%{$request->keyword}%");
        if ($request->is_hot) $query->where('is_hot', 1);
        if ($request->is_new) $query->where('is_new', 1);
        if ($request->is_recommend) $query->where('is_recommend', 1);
        if ($request->min_price) $query->where('price', '>=', $request->min_price);
        if ($request->max_price) $query->where('price', '<=', $request->max_price);

        $sort = $request->sort ?: 'default';
        if ($sort == 'sales') $query->orderBy('sales', 'desc');
        elseif ($sort == 'price_asc') $query->orderBy('price', 'asc');
        elseif ($sort == 'price_desc') $query->orderBy('price', 'desc');
        elseif ($sort == 'new') $query->orderBy('created_at', 'desc');
        else $query->orderBy('id', 'desc');

        $page = $request->page ?: 1;
        $limit = $request->limit ?: 20;
        $list = $query->select('id', 'name', 'main_image', 'price', 'market_price', 'sales', 'is_hot', 'is_new')
            ->paginate($limit, ['*'], 'page', $page);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function show($id)
    {
        $product = Product::with(['category:id,name', 'skus' => function($q) { $q->where('status', 1); }])->find($id);
        if (!$product || $product->status != 1) return $this->error('商品不存在或已下架', 404);

        $product->increment('views');

        return $this->success([
            'id' => $product->id,
            'name' => $product->name,
            'subtitle' => $product->subtitle,
            'main_image' => $product->main_image,
            'images' => $product->images ?: [$product->main_image],
            'price' => $product->price,
            'market_price' => $product->market_price,
            'stock' => $product->stock,
            'sales' => $product->sales,
            'views' => $product->views,
            'unit' => $product->unit,
            'is_sku' => $product->is_sku,
            'description' => $product->description,
            'category' => $product->category,
            'skus' => $product->skus,
            'is_free_shipping' => $product->is_free_shipping,
            'shipping_fee' => $product->shipping_fee,
        ]);
    }

    public function categories()
    {
        $categories = ProductCategory::where('status', 1)->where('parent_id', 0)
            ->with(['children' => function($q) { $q->where('status', 1)->select('id', 'parent_id', 'name', 'icon', 'sort'); }])
            ->select('id', 'name', 'icon', 'image', 'sort')
            ->orderBy('sort')->get();
        return $this->success($categories);
    }

    public function hot(Request $request)
    {
        $limit = $request->limit ?: 10;
        $list = Product::where('status', 1)->where('is_hot', 1)
            ->select('id', 'name', 'main_image', 'price', 'market_price', 'sales')
            ->orderBy('sales', 'desc')->limit($limit)->get();
        return $this->success($list);
    }

    public function new(Request $request)
    {
        $limit = $request->limit ?: 10;
        $list = Product::where('status', 1)->where('is_new', 1)
            ->select('id', 'name', 'main_image', 'price', 'market_price', 'sales')
            ->orderBy('created_at', 'desc')->limit($limit)->get();
        return $this->success($list);
    }
}
