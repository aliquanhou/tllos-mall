<?php
namespace App\Modules\SystemConfig\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class FileManagerController extends BaseController {
    public function index(Request $request) {
        $query = DB::table('file_managers');
        if ($request->category) $query->where('category', $request->category);
        if ($request->keyword) $query->where('name','like','%'.$request->keyword.'%');
        $list = $query->orderBy('id','desc')->paginate($request->limit ?? 20);
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function destroy($id) {
        $file = DB::table('file_managers')->where('id',$id)->first();
        if ($file && $file->path && file_exists(public_path($file->path))) @unlink(public_path($file->path));
        DB::table('file_managers')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
}
