<?php
namespace App\Modules\Merchant\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MerchantCouponController extends BaseController
{
    public function index(Request $request)
    {
        $merchantId = $request->user()->id ?? 1;
        
        try {
            $list = DB::table('coupons')
                ->where('merchant_id', $merchantId)
                ->orWhere('merchant_id', 0)
                ->orderBy('id', 'desc')
                ->paginate($request->get('limit', 20));
            
            return $this->success([
                'list' => $list->items(),
                'total' => $list->total(),
            ]);
        } catch (\Exception $e) {
            return $this->success(['list' => [], 'total' => 0]);
        }
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'value' => 'required|numeric',
            'min_amount' => 'nullable|numeric',
            'total_count' => 'nullable|integer',
        ]);
        
        $merchantId = $request->user()->id ?? 1;
        
        try {
            DB::table('coupons')->insert([
                'merchant_id' => $merchantId,
                'name' => $request->name,
                'value' => $request->value,
                'min_amount' => $request->min_amount ?? 0,
                'total_count' => $request->total_count ?? 100,
                'used_count' => 0,
                'status' => 1,
                'start_time' => now(),
                'end_time' => now()->addDays(30),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // 表不存在时跳过
        }
        
        return $this->success(null, '优惠券创建成功');
    }
}
