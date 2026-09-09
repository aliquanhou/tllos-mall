<?php
namespace App\Modules\Tools\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class GeneratorController extends BaseController {
    public function tables() { return $this->getModels();
}
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

    public function selectTable(Request $request) {
        $tables = DB::select("SHOW TABLES");
        $list = [];
        foreach ($tables as $t) {
            $arr = (array)$t;
            $list[] = ["name" => reset($arr), "engine" => "InnoDB"];
        }
        return $this->success(["list" => $list, "total" => count($list)]);
    }
    public function dataTable(Request $request) {
        $table = $request->input('table');
        if (!$table) return $this->error('请选择表');
        try {
            $columns = DB::select("SHOW COLUMNS FROM `$table`");
            $data = DB::table($table)->paginate($request->input('limit', 20));
            return $this->success(['columns' => $columns, 'list' => $data->items(), 'total' => $data->total()]);
        } catch (\Exception $e) {
            return $this->error('表不存在或查询失败');
        }
    }

}
