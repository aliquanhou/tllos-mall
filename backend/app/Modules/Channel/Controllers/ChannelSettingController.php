<?php
namespace App\Modules\Channel\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChannelSettingController extends BaseController
{
    public function getConfig($channel) {
        $settings = DB::table('channel_settings')->where('channel', $channel)->pluck('value','key')->toArray();
        return $this->success($settings);
    }
    public function setConfig(Request $request, $channel) {
        foreach($request->all() as $k=>$v){
            if(is_string($k)&&!empty($k)){
                DB::table('channel_settings')->updateOrInsert(
                    ['channel'=>$channel,'key'=>$k],
                    ['value'=>$v,'updated_at'=>now()]
                );
            }
        }
        return $this->success(null,'保存成功');
    }
}
