<?php
namespace App\Modules\User\Controllers;

use App\Core\Controllers\BaseController;
use App\Modules\Order\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class InvoiceController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('invoices')->orderBy('id', 'desc');
        if ($request->user_id) $query->where('user_id', $request->user_id);
        if ($request->order_no) $query->where('order_no', 'like', '%' . $request->order_no . '%');
        if ($request->status !== null && $request->status !== '') $query->where('status', $request->status);
        $total = $query->count();
        $list = $query->offset(($request->page ?? 1) - 1)->limit($request->limit ?? 20)->get();
        return $this->success([
            'list' => $list, 'total' => $total,
            'stats' => [
                'total' => DB::table('invoices')->count(),
                'pending' => DB::table('invoices')->where('status', 0)->count(),
                'issued' => DB::table('invoices')->where('status', 1)->count(),
                'total_amount' => DB::table('invoices')->where('status', 1)->sum('amount'),
            ],
        ]);
    }

    public function userIndex(Request $request)
    {
        $userId = $request->user()->id;
        $list = DB::table('invoices')->where('user_id', $userId)->orderBy('id', 'desc')->get();
        return $this->success(['list' => $list, 'total' => count($list)]);
    }

    public function apply(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer', 'type' => 'required|integer|in:1,2',
            'title' => 'required|string', 'email' => 'required|email',
            'tax_no' => 'nullable|string', 'company_address' => 'nullable|string',
            'company_phone' => 'nullable|string', 'bank_name' => 'nullable|string', 'bank_account' => 'nullable|string',
        ]);
        $userId = $request->user()->id;
        $order = Order::where('id', $request->order_id)->where('user_id', $userId)->first();
        if (!$order) return $this->error('订单不存在', 404);
        if ($order->status < 1) return $this->error('订单未支付，不能申请发票');
        $exists = DB::table('invoices')->where('order_id', $order->id)->where('status', '!=', 2)->first();
        if ($exists) return $this->error('该订单已申请发票');
        if ($request->type == 2 && !$request->tax_no) return $this->error('企业发票必须填写税号');

        $invoiceNo = 'INV' . date('YmdHis') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        DB::table('invoices')->insert([
            'invoice_no' => $invoiceNo, 'order_id' => $order->id, 'order_no' => $order->order_no,
            'user_id' => $userId, 'type' => $request->type, 'title' => $request->title,
            'tax_no' => $request->tax_no, 'company_address' => $request->company_address,
            'company_phone' => $request->company_phone, 'bank_name' => $request->bank_name,
            'bank_account' => $request->bank_account, 'email' => $request->email,
            'amount' => $order->pay_amount, 'status' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return $this->success(['invoice_no' => $invoiceNo], '发票申请已提交');
    }

    public function issue(Request $request, $id)
    {
        $request->validate(['invoice_url' => 'nullable|string', 'remark' => 'nullable|string']);
        $invoice = DB::table('invoices')->where('id', $id)->first();
        if (!$invoice) return $this->error('发票申请不存在', 404);
        if ($invoice->status != 0) return $this->error('该发票已处理');
        DB::table('invoices')->where('id', $id)->update([
            'status' => 1, 'invoice_url' => $request->invoice_url ?? '', 'remark' => $request->remark,
            'issued_at' => now(), 'updated_at' => now(),
        ]);
        return $this->success(null, '发票已开具');
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['remark' => 'required|string']);
        $invoice = DB::table('invoices')->where('id', $id)->first();
        if (!$invoice) return $this->error('发票申请不存在', 404);
        DB::table('invoices')->where('id', $id)->update(['status' => 2, 'remark' => $request->remark, 'updated_at' => now()]);
        return $this->success(null, '已拒绝');
    }
}
