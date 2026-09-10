<?php
namespace App\Modules\Channel\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfficialAccountMenuController extends BaseController
{
    public function detail() {
        $menus = DB::table('channel_settings')->where('channel','oa')->where('key','like','menu_%')->get();
        return $this->success(['menus'=>$menus]);
    }
    public function save(Request $request) {
        $menus = $request->input('menus', []);
        foreach($menus as $i=>$menu){
            DB::table('channel_settings')->updateOrInsert(
                ['channel'=>'oa','key'=>'menu_'.$i],
                ['value'=>json_encode($menu,JSON_UNESCAPED_UNICODE),'updated_at'=>now()]
            );
        }
        return $this->success(null,'保存成功');
    }
    public function saveAndPublish(Request $request) {
        $this->save($request);
        return $this->success(null,'保存并发布成功(模拟)');
    }
}
