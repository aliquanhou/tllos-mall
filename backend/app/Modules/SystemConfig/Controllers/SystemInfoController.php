<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class SystemInfoController extends BaseController {
    public function index() { return $this->getInfo(); }
    public function info() { return $this->getInfo(); }
    private function getInfo() {
        try {
            $info = DB::table('system_info')->first();
            if (!$info) {
                $info = ['version'=>'1.0.0','php_version'=>PHP_VERSION,'mysql_version'=>'10.11.18-MariaDB','server_software'=>isset($_SERVER['SERVER_SOFTWARE'])?$_SERVER['SERVER_SOFTWARE']:'nginx','os'=>PHP_OS];
            } else { $info = (array)$info; }
            $stats = [
                'goods_count'=>DB::table('products')->count(),
                'order_count'=>DB::table('orders')->count(),
                'user_count'=>DB::table('users')->count(),
                'merchant_count'=>DB::table('shops')->count(),
                'today_orders'=>DB::table('orders')->whereDate('created_at',date('Y-m-d'))->count(),
                'today_sales'=>DB::table('orders')->whereDate('created_at',date('Y-m-d'))->sum('total_amount')??0,
            ];
            return $this->success(['info'=>$info,'stats'=>$stats]);
        } catch (\Exception $e) {
            return $this->success(['info'=>['version'=>'1.0.0','php_version'=>PHP_VERSION,'os'=>PHP_OS],'stats'=>['goods_count'=>0,'order_count'=>0,'user_count'=>0,'merchant_count'=>0],'error'=>$e->getMessage()]);
        }
    }
}
