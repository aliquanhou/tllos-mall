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
            $arr = (array)$table;
            $name = array_values($arr)[0];
            $list[] = ['name' => $name];
        }
        return $this->success($list);
    }
    public function columns($table) {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $columns = DB::select("SHOW COLUMNS FROM `$table`");
        return $this->success($columns);
    }
    public function generate(Request $request) {
        return $this->success(['table' => $request->input('table')], '生成成功');
    }
}
