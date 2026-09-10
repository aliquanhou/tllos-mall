<?php
namespace App\Modules\Admin\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class DashboardController extends BaseController {
    public function stats() {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $monthStart = date('Y-m-01');

        $stats = [
            // 总览
            'total_users' => DB::table('users')->count(),
            'total_orders' => DB::table('orders')->count(),
            'total_products' => DB::table('products')->count(),
            'total_merchants' => DB::table('shops')->count(),
            'total_sales' => DB::table('orders')->where('status', '>=', 1)->sum('total_amount') ?? 0,

            // 今日
            'today_orders' => DB::table('orders')->whereDate('created_at', $today)->count(),
            'today_sales' => DB::table('orders')->whereDate('created_at', $today)->where('status', '>=', 1)->sum('total_amount') ?? 0,
            'today_new_users' => DB::table('users')->whereDate('created_at', $today)->count(),

            // 昨日
            'yesterday_orders' => DB::table('orders')->whereDate('created_at', $yesterday)->count(),
            'yesterday_sales' => DB::table('orders')->whereDate('created_at', $yesterday)->where('status', '>=', 1)->sum('total_amount') ?? 0,

            // 本月
            'month_orders' => DB::table('orders')->whereDate('created_at', '>=', $monthStart)->count(),
            'month_sales' => DB::table('orders')->whereDate('created_at', '>=', $monthStart)->where('status', '>=', 1)->sum('total_amount') ?? 0,

            // 待处理
            'pending_orders' => DB::table('orders')->where('status', 1)->count(),
            'pending_after_sales' => DB::table('order_after_sales')->where('status', 0)->count(),
            'pending_merchants' => DB::table('shops')->where('status', 0)->count(),
            'pending_withdraws' => DB::table('merchant_withdraws')->where('status', 0)->count(),

            // 库存预警
            'stock_warning_count' => DB::table('products')->whereColumn('stock', '<=', 'warning_stock')->count(),
        ];

        return $this->success($stats);
    }

    public function recentOrders() {
        $list = DB::table('orders as o')
            ->leftJoin('users as u', 'o.user_id', '=', 'u.id')
            ->select('o.id', 'o.order_no', 'o.total_amount', 'o.status', 'o.created_at', 'u.nickname', 'u.mobile')
            ->orderBy('o.id', 'desc')
            ->limit(10)
            ->get();

        $statusMap = [0=>'待付款',1=>'待发货',2=>'待收货',3=>'已完成',4=>'已取消',5=>'已退款'];
        $statusTypeMap = [0=>'warning',1=>'primary',2=>'success',3=>'success',4=>'info',5=>'danger'];

        foreach ($list as $item) {
            $item->status_text = $statusMap[$item->status] ?? '未知';
            $item->status_type = $statusTypeMap[$item->status] ?? 'info';
            $item->customer = $item->nickname ?: $item->mobile ?: '匿名用户';
        }

        return $this->success(['list' => $list, 'total' => count($list)]);
    }

    public function salesTrend() {
        $days = [];
        $sales = [];
        $orders = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $days[] = $date;
            $sales[] = DB::table('orders')->whereDate('created_at', $date)->where('status', '>=', 1)->sum('total_amount') ?? 0;
            $orders[] = DB::table('orders')->whereDate('created_at', $date)->count();
        }
        return $this->success(['days' => $days, 'sales' => $sales, 'orders' => $orders]);
    }
}
