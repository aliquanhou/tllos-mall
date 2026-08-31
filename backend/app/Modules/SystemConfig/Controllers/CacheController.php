<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheController extends BaseController
{
    public function clear() {
        Cache::flush();
        DB::table('admin_logs')->insert(['admin_id'=>1,'admin_name'=>'admin','module'=>'系统设置','action'=>'clear','content'=>'清理系统缓存','ip'=>request()->ip(),'created_at'=>now()]);
        return $this->success(null,'缓存清理成功');
    }
}
