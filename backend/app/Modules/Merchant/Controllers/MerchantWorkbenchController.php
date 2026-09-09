<?php
namespace App\Modules\Merchant\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MerchantWorkbenchController extends BaseController {
    private function getShopId($request) {
        return DB::table('shops')->where('user_id', $request->user()->id)->value('id') ?? 0;
    }
    public function index(Request $request) {
        $shopId = $this->getShopId($request);
        $today = date('Y-m-d');
        $todayOrders = DB::table('orders')->where('merchant_id', $shopId)->whereDate('created_at', $today)->count();
        $todayAmount = DB::table('orders')->where('merchant_id', $shopId)->whereDate('created_at', $today)->where('status', '>=', 2)->sum('total_amount') ?? 0;
        $pendingShip = DB::table('orders')->where('merchant_id', $shopId)->where('status', 1)->count();
        // 售后表可能没有merchant_id，用order关联查询
        $pendingRefund = DB::table('order_after_sales as oas')
            ->join('orders as o', 'oas.order_id', '=', 'o.id')
            ->where('o.merchant_id', $shopId)
            ->where('oas.status', 0)
            ->count();
        $goodsCount = DB::table('products')->where('merchant_id', $shopId)->count();
        $totalAmount = DB::table('orders')->where('merchant_id', $shopId)->where('status', '>=', 2)->sum('total_amount') ?? 0;
        return $this->success([
            'today_orders' => $todayOrders,
            'today_amount' => $todayAmount,
            'pending_ship' => $pendingShip,
            'pending_refund' => $pendingRefund,
            'goods_count' => $goodsCount,
            'total_amount' => $totalAmount,
        ]);
    }
}
