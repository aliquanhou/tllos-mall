<?php
namespace App\Modules\Home\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends BaseController
{
    public function index()
    {
        $banners = DB::table('banners')->where('position', 'home')->where('status', 1)
            ->where(function($q) { $q->whereNull('start_time')->orWhere('start_time', '<=', now()); })
            ->where(function($q) { $q->whereNull('end_time')->orWhere('end_time', '>=', now()); })
            ->orderBy('sort')->select('id', 'title', 'image', 'link_type', 'link_value')->get();

        $navigations = DB::table('navigations')->where('position', 'home')->where('status', 1)
            ->orderBy('sort')->select('id', 'name', 'icon', 'link_type', 'link_value')->limit(10)->get();

        $categories = DB::table('product_categories')->where('parent_id', 0)->where('status', 1)
            ->orderBy('sort')->select('id', 'name', 'icon', 'image')->limit(8)->get();

        $hotProducts = Product::where('status', 1)->where('is_hot', 1)
            ->select('id', 'name', 'main_image', 'price', 'market_price', 'sales')
            ->orderBy('sales', 'desc')->limit(6)->get();

        $newProducts = Product::where('status', 1)->where('is_new', 1)
            ->select('id', 'name', 'main_image', 'price', 'market_price', 'sales')
            ->orderBy('created_at', 'desc')->limit(6)->get();

        $recommendProducts = Product::where('status', 1)->where('is_recommend', 1)
            ->select('id', 'name', 'main_image', 'price', 'market_price', 'sales')
            ->orderBy('id', 'desc')->limit(10)->get();

        $seckills = DB::table('seckills')->where('status', 1)
            ->where('start_time', '<=', now())->where('end_time', '>=', now())
            ->orderBy('sort')->first();

        $seckillProducts = [];
        if ($seckills) {
            $seckillProducts = DB::table('seckill_products')->where('seckill_id', $seckills->id)
                ->where('status', 1)->join('products', 'seckill_products.product_id', '=', 'products.id')
                ->select('products.id', 'products.name', 'products.main_image', 'seckill_products.seckill_price',
                    'products.price as original_price', 'seckill_products.seckill_stock', 'seckill_products.sold_count')
                ->orderBy('seckill_products.sort')->limit(10)->get();
        }

        return $this->success([
            'banners' => $banners,
            'navigations' => $navigations,
            'categories' => $categories,
            'hot_products' => $hotProducts,
            'new_products' => $newProducts,
            'recommend_products' => $recommendProducts,
            'seckill' => $seckills,
            'seckill_products' => $seckillProducts,
        ]);
    }

    public function config()
    {
        $configs = DB::table('system_configs')->where('status', 1)
            ->whereIn('group', ['basic', 'trade', 'user'])
            ->pluck('value', 'key');
        return $this->success($configs);
    }
}
