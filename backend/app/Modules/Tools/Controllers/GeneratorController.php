<?php
namespace App\Modules\Tools\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GeneratorController extends BaseController
{
    public function getModels() {
        $tables = DB::select('SHOW TABLES');
        $list = [];
        foreach($tables as $t){
            $name = array_values((array)$t)[0];
            $cols = DB::select("SHOW COLUMNS FROM `$name`");
            $list[] = ['name'=>$name,'columns'=>count($cols)];
        }
        return $this->success(['list'=>$list,'total'=>count($list)]);
    }
    public function selectTable(Request $request) {
        $table = $request->input('table');
        if(!$table) return $this->error('请指定表名');
        $columns = DB::select("SHOW COLUMNS FROM `$table`");
        return $this->success(['table'=>$table,'columns'=>$columns]);
    }
    public function dataTable(Request $request) {
        $table = $request->input('table');
        if(!$table) return $this->error('请指定表名');
        $total = DB::table($table)->count();
        $page = $request->get('page',1); $limit = $request->get('limit',20);
        $list = DB::table($table)->offset(($page-1)*$limit)->limit($limit)->get();
        return $this->success(['list'=>$list,'total'=>$total,'page'=>$page,'limit'=>$limit]);
    }
    public function generate(Request $request) {
        return $this->success(['msg'=>'代码生成成功(模拟)','files'=>['Controller.php','Model.php','migration.php']]);
    }
    public function preview(Request $request) {
        return $this->success(['code'=>'// 生成的代码预览','msg'=>'预览成功']);
    }
    public function download(Request $request) {
        return $this->success(['msg'=>'下载成功(模拟)']);
    }
}
