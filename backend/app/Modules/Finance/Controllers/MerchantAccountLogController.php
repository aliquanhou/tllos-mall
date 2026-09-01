<?php
namespace App\Modules\Finance\Controllers;
use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class MerchantAccountLogController extends BaseController {
    public function index(Request $request) {
        $query = DB::table('merchant_account_logs as l')
            ->leftJoin('shops as s', 'l.merchant_id', '=', 's.id')
            ->select('l.*', 's.shop_name');
        if ($request->merchant_id) $query->where('l.merchant_id', $request->merchant_id);
        if ($request->type) $query->where('l.type', $request->type);
        $total = $query->count();
        $list = $query->orderBy('l.id', 'desc')->paginate($request->limit ?: 20);
        return $this->success(['list' => $list->items(), 'total' => $total, 'page' => $list->currentPage(), 'limit' => $list->perPage()]);
    }
}
