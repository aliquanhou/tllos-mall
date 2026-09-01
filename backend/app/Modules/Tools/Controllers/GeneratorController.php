<?php
namespace App\Modules\Tools\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class GeneratorController extends BaseController {
    public function tables() { return $this->getModels(); }
    public function getModels() {
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
        return $this->success(['table' => $request->input('table')], '生成成功');
    }
    public function preview(Request $request) {
        return $this->success(['table' => $request->input('table')], '预览成功');
    }
    public function download(Request $request) {
        return $this->success(['table' => $request->input('table')], '下载成功');
    }
    public function data(Request $request) {
        return $this->success(['tables' => []]);
    }
}
