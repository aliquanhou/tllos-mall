<?php
namespace App\Modules\Marketing\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MarketingController extends BaseController {
    // 优惠券
    public function couponList(Request $request) {
        $list = DB::table('coupons')->orderBy('id','desc')->paginate($request->get('limit',20));
        return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
    }
    public function couponStore(Request $request) {
        $data = $request->only(['name','type','discount_amount','min_amount','start_time','end_time','total_count','status']);
        $data['created_at'] = now();
        $id = DB::table('coupons')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function couponUpdate(Request $request, $id) {
        $data = $request->only(['name','type','discount_amount','min_amount','start_time','end_time','total_count','status']);
        DB::table('coupons')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function couponDestroy($id) {
        DB::table('coupons')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }

    // 秒杀
    public function seckillList(Request $request) {
        try {
            $query = DB::table('seckills');
            if ($request->keyword) $query->where('name','like','%'.$request->keyword.'%');
            $list = $query->orderBy('id','desc')->paginate($request->get('limit',20));
            return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
    public function seckillStore(Request $request) {
        $data = $request->only(['name','start_time','end_time','status']);
        $data['created_at'] = now();
        $id = DB::table('seckills')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function seckillUpdate(Request $request, $id) {
        $data = $request->only(['name','start_time','end_time','status']);
        DB::table('seckills')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function seckillDestroy($id) {
        DB::table('seckills')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }
    public function seckillGoods($id) {
        try {
            $list = DB::table('seckill_goods')->where('seckill_id',$id)->get();
            return $this->success($list);
        } catch (\Exception $e) {
            return $this->success([], $e->getMessage());
        }
    }

    // 拼团
    public function groupList(Request $request) {
        try {
            $query = DB::table('groups');
            if ($request->keyword) $query->where('name','like','%'.$request->keyword.'%');
            $list = $query->orderBy('id','desc')->paginate($request->get('limit',20));
            return $this->success(['list'=>$list->items(),'total'=>$list->total()]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
    public function groupStore(Request $request) {
        $data = $request->only(['name','goods_id','group_price','group_num','start_time','end_time','status']);
        $data['created_at'] = now();
        $id = DB::table('groups')->insertGetId($data);
        return $this->success(['id'=>$id],'创建成功');
    }
    public function groupUpdate(Request $request, $id) {
        $data = $request->only(['name','goods_id','group_price','group_num','start_time','end_time','status']);
        DB::table('groups')->where('id',$id)->update($data);
        return $this->success(null,'更新成功');
    }
    public function groupDestroy($id) {
        DB::table('groups')->where('id',$id)->delete();
        return $this->success(null,'删除成功');
    }

    // 会员折扣
    public function memberDiscount(Request $request) {
        try {
            $list = DB::table('user_levels')->orderBy('id','asc')->get();
            return $this->success(['list'=>$list,'total'=>count($list)]);
        } catch (\Exception $e) {
            return $this->success(['list'=>[],'total'=>0,'error'=>$e->getMessage()]);
        }
    }
}
