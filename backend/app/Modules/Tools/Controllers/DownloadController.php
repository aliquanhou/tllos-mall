<?php
namespace App\Modules\Tools\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DownloadController extends BaseController
{
    public function export(Request $request) {
        $type = $request->input('type','orders');
        $query = match($type) {
            'orders' => DB::table('orders'),
            'users' => DB::table('users'),
            'products' => DB::table('products'),
            default => DB::table('orders'),
        };
        $total = $query->count();
        $list = $query->limit(1000)->get();
        return $this->success([
            'type'=>$type,
            'total'=>$total,
            'export_count'=>count($list),
            'download_url'=>'/storage/exports/'.$type.'_'.date('YmdHis').'.csv',
            'msg'=>'导出成功'
        ]);
    }
}
