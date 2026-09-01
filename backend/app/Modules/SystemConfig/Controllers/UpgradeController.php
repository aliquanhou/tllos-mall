<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UpgradeController extends BaseController
{
    public function lists() {
        $list = [
            ['version'=>'1.0.0','release_date'=>'2026-08-01','description'=>'初始版本','status'=>'current'],
            ['version'=>'1.1.0','release_date'=>'2026-09-01','description'=>'优化性能，修复已知问题','status'=>'available'],
        ];
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function downloadPkg(Request $request) {
        return $this->success(['msg'=>'下载升级包成功(模拟)','version'=>$request->input('version')]);
    }
    public function upgrade(Request $request) {
        return $this->success(['msg'=>'系统升级成功(模拟)','version'=>$request->input('version')]);
    }
}
