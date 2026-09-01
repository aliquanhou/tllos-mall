<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CrontabController extends BaseController {
    public function index(Request $request) {
        $list = DB::table('crontab_tasks')->orderBy('id','desc')->paginate($request->limit ?? 20);
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function store(Request $request) {
        $data = $request->only(['name','command','cron_expression','status']);
        $data['created_at'] = now(); $data['updated_at'] = now();
        $id = DB::table('crontab_tasks')->insertGetId($data);
        return $this->success(DB::table('crontab_tasks')->where('id',$id)->first(),'创建成功');
    }
    public function update(Request $request, $id) {
        $data = $request->only(['name','command','cron_expression','status']);
        $data['updated_at'] = now();
        DB::table('crontab_tasks')->where('id',$id)->update($data);
        return $this->success(DB::table('crontab_tasks')->where('id',$id)->first(),'更新成功');
    }
    public function destroy($id) { DB::table('crontab_tasks')->where('id',$id)->delete(); return $this->success(null,'删除成功'); }
    public function run($id) {
        $task = DB::table('crontab_tasks')->where('id',$id)->first();
        if (!$task) return $this->error('任务不存在');
        DB::table('crontab_tasks')->where('id',$id)->update(['last_run_at'=>now()]);
        return $this->success(null,'任务已执行');
    }
}
