<?php
namespace App\Modules\Tools\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class GeneratorController extends BaseController {
    public function tables() {
        $tables = DB::select('SHOW TABLES');
        $list = [];
        foreach ($tables as $table) {
            $name = array_values((array)$table)[0];
            $comment = DB::select("SELECT TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$name]);
            $list[] = ['name'=>$name,'comment'=>$comment[0]->TABLE_COMMENT ?? ''];
        }
        return $this->success($list);
    }
    public function columns($table) {
        $columns = DB::select("SHOW FULL COLUMNS FROM `$table`");
        return $this->success($columns);
    }
    public function generate(Request $request) {
        $table = $request->table;
        $module = $request->module ?? 'Admin';
        return $this->success(['table'=>$table,'module'=>$module],'代码生成成功（模拟）');
    }
}
