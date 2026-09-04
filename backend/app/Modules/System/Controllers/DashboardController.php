<?php
namespace App\Modules\System\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardController extends BaseController
{
    public function overview()
    {
        try {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();

            $paidStatuses = [1, 2, 3, 5, 6];

            $totalGmv = DB::table('orders')->whereIn('status', $paidStatuses)->sum('pay_amount');
            $todayGmv = DB::table('orders')->whereIn('status', $paidStatuses)->whereDate('created_at', $today)->sum('pay_amount');
            $yesterdayGmv = DB::table('orders')->whereIn('status', $paidStatuses)->whereDate('created_at', $yesterday)->sum('pay_amount');

            $totalOrders = DB::table('orders')->count();
            $todayOrders = DB::table('orders')->whereDate('created_at', $today)->count();
            $totalUsers = DB::table('users')->count();
            $todayNewUsers = DB::table('users')->whereDate('created_at', $today)->count();
            $totalProducts = DB::table('products')->where('status', 1)->count();

            $pendingOrders = DB::table('orders')->where('status', 1)->count();
            $refundOrders = DB::table('orders')->where('status', 6)->count();
            $refundRate = $totalOrders > 0 ? round($refundOrders / $totalOrders * 100, 2) : 0;

            // 安全查询可能不存在的表
            $pendingAfterSales = 0;
            $pendingWithdraws = 0;
            $totalMerchants = 0;
            try { $pendingAfterSales = DB::table('order_after_sales')->where('status', 0)->count(); } catch (\Exception $e) {}
            try { $pendingWithdraws = DB::table('user_withdraws')->where('status', 0)->count(); } catch (\Exception $e) {}
            try { $totalMerchants = DB::table('merchants')->count(); } catch (\Exception $e) {}

            return $this->success([
                'gmv' => ['total' => $totalGmv, 'today' => $todayGmv, 'yesterday' => $yesterdayGmv],
                'orders' => ['total' => $totalOrders, 'today' => $todayOrders, 'pending' => $pendingOrders],
                'users' => ['total' => $totalUsers, 'today_new' => $todayNewUsers],
                'merchants' => ['total' => $totalMerchants],
                'products' => ['total' => $totalProducts],
                'refund_rate' => $refundRate,
                'pending' => ['orders' => $pendingOrders, 'after_sales' => $pendingAfterSales, 'withdraws' => $pendingWithdraws],
            ]);
        } catch (\Exception $e) {
            return $this->error('数据加载失败: ' . $e->getMessage());
        }
    }

    public function orderTrend(Request $request)
    {
        try {
            $days = min($request->days ?: 7, 30);
            $startDate = Carbon::today()->subDays($days - 1);
            $trend = [];
            for ($i = 0; $i < $days; $i++) {
                $date = $startDate->copy()->addDays($i);
                $dateStr = $date->format('Y-m-d');
                $trend[] = [
                    'date' => $dateStr,
                    'orders' => DB::table('orders')->whereDate('created_at', $date)->count(),
                    'gmv' => DB::table('orders')->whereIn('status', [1,2,3,5,6])->whereDate('created_at', $date)->sum('pay_amount'),
                ];
            }
            return $this->success(['trend' => $trend]);
        } catch (\Exception $e) {
            return $this->error('数据加载失败: ' . $e->getMessage());
        }
    }

    public function merchantRanking(Request $request)
    {
        try {
            $limit = min($request->limit ?: 10, 50);
            $merchants = DB::table('orders')
                ->join('merchants', 'orders.merchant_id', '=', 'merchants.id')
                ->whereIn('orders.status', [1,2,3,5,6])
                ->select('merchants.id', 'merchants.contact_name',
                    DB::raw('COUNT(orders.id) as order_count'),
                    DB::raw('SUM(orders.pay_amount) as total_sales'))
                ->groupBy('merchants.id', 'merchants.contact_name')
                ->orderByDesc('total_sales')
                ->limit($limit)
                ->get();
            return $this->success(['list' => $merchants]);
        } catch (\Exception $e) {
            return $this->success(['list' => [], 'error' => $e->getMessage()]);
        }
    }

    public function productRanking(Request $request)
    {
        try {
            $limit = min($request->limit ?: 10, 50);
            $products = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select('products.id', 'products.name', 'products.main_image',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.pay_amount) as total_sales'))
                ->groupBy('products.id', 'products.name', 'products.main_image')
                ->orderByDesc('total_sales')
                ->limit($limit)
                ->get();
            return $this->success(['list' => $products]);
        } catch (\Exception $e) {
            return $this->success(['list' => [], 'error' => $e->getMessage()]);
        }
    }

    public function userActivity()
    {
        try {
            $today = Carbon::today();
            $dau = DB::table('orders')->whereDate('created_at', $today)->distinct('user_id')->count('user_id');
            $weekStart = Carbon::now()->startOfWeek();
            $wau = DB::table('orders')->where('created_at', '>=', $weekStart)->distinct('user_id')->count('user_id');
            $monthStart = Carbon::now()->startOfMonth();
            $mau = DB::table('orders')->where('created_at', '>=', $monthStart)->distinct('user_id')->count('user_id');
            return $this->success(['dau' => $dau, 'wau' => $wau, 'mau' => $mau]);
        } catch (\Exception $e) {
            return $this->error('数据加载失败: ' . $e->getMessage());
        }
    }

    public function categoryStats()
    {
        try {
            $categories = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
                ->select('product_categories.id', 'product_categories.name',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.pay_amount) as total_sales'))
                ->groupBy('product_categories.id', 'product_categories.name')
                ->orderByDesc('total_sales')
                ->limit(10)
                ->get();
            return $this->success(['list' => $categories]);
        } catch (\Exception $e) {
            return $this->success(['list' => [], 'error' => $e->getMessage()]);
        }
    }

    public function recentOrders(Request $request)
    {
        try {
            $limit = min($request->limit ?: 10, 50);
            $orders = DB::table('orders')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->select('orders.id', 'orders.order_no', 'orders.status', 'orders.pay_amount',
                    'orders.total_amount', 'orders.created_at',
                    'users.nickname as user_nickname', 'users.mobile as user_mobile')
                ->orderByDesc('orders.created_at')
                ->limit($limit)
                ->get();

            // 状态映射
            $statusMap = [0 => '已取消', 1 => '待付款', 2 => '待发货', 3 => '待收货', 4 => '已完成', 5 => '已关闭', 6 => '退款中'];
            foreach ($orders as $order) {
                $order->status_text = $statusMap[$order->status] ?? '未知';
            }

            return $this->success(['list' => $orders]);
        } catch (\Exception $e) {
            return $this->success(['list' => [], 'error' => $e->getMessage()]);
        }
    }

}
