<?php
namespace App\Modules\Tools\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class GeneratorController extends BaseController {
    public function tables() {
        try {
            $tables = DB::select('SHOW TABLES');
            $list = [];
            foreach ($tables as $table) {
                $arr = (array)$table;
                $name = array_values($arr)[0];
                $list[] = ['name' => $name, 'comment' => ''];
            }
            return $this->success($list);
        } catch (\Exception $e) {
            return $this->success([], $e->getMessage());
        }
    }
    public function columns($table) {
        try {
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $columns = DB::select("SHOW FULL COLUMNS FROM `$table`");
            return $this->success($columns);
        } catch (\Exception $e) {
            return $this->success([], $e->getMessage());
        }
    }
    public function generate(Request $request) {
        $table = $request->input('table');
        $module = $request->input('module', 'Admin');
        return $this->success(['table' => $table, 'module' => $module], '代码生成成功（模拟）');
    }
}
