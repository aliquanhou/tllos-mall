<?php

namespace App\Modules\Finance\Controllers;

use App\Core\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MerchantAccountLogController extends BaseController
{
    public function index(Request $request)
    {
        $query = DB::table('merchant_account_logs as l')
            ->leftJoin('merchants as m', 'l.merchant_id', '=', 'm.id')
            ->select('l.*', 'm.name as merchant_name');

        if ($request->filled('merchant_id')) {
            $query->where('l.merchant_id', $request->merchant_id);
        }
        if ($request->filled('type')) {
            $query->where('l.type', $request->type);
        }
        if ($request->filled('keyword')) {
            $query->where(function($q) use ($request) {
                $q->where('l.order_no', 'like', '%' . $request->keyword . '%')
                  ->orWhere('l.remark', 'like', '%' . $request->keyword . '%')
                  ->orWhere('m.name', 'like', '%' . $request->keyword . '%');
            });
        }
        if ($request->filled('start_time')) {
            $query->where('l.created_at', '>=', $request->start_time);
        }
        if ($request->filled('end_time')) {
            $query->where('l.created_at', '<=', $request->end_time);
        }

        $total = $query->count();
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);
        $list = $query->orderBy('l.id', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get();

        $stats = DB::table('merchant_account_logs as l')
            ->select('l.type', DB::raw('SUM(l.amount) as total_amount, COUNT(*) as count'))
            ->groupBy('l.type')
            ->get();

        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'stats' => $stats
        ]);
    }

    public function show($id)
    {
        $log = DB::table('merchant_account_logs as l')
            ->leftJoin('merchants as m', 'l.merchant_id', '=', 'm.id')
            ->select('l.*', 'm.name as merchant_name', 'm.contact_mobile')
            ->where('l.id', $id)
            ->first();
        if (!$log) {
            return $this->error('记录不存在');
        }
        return $this->success($log);
    }

    public function stats(Request $request)
    {
        $query = DB::table('merchant_account_logs');
        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->merchant_id);
        }
        if ($request->filled('start_time')) {
            $query->where('created_at', '>=', $request->start_time);
        }
        if ($request->filled('end_time')) {
            $query->where('created_at', '<=', $request->end_time);
        }

        $income = (clone $query)->where('type', 1)->sum('amount');
        $withdraw = (clone $query)->where('type', 2)->sum('amount');
        $commission = (clone $query)->where('type', 3)->sum('amount');
        $refund = (clone $query)->where('type', 4)->sum('amount');
        $adjust = (clone $query)->where('type', 5)->sum('amount');
        $totalCount = (clone $query)->count();

        return $this->success([
            'total_income' => $income,
            'total_withdraw' => $withdraw,
            'total_commission' => $commission,
            'total_refund' => $refund,
            'total_adjust' => $adjust,
            'total_count' => $totalCount,
            'net_income' => $income - $withdraw - $commission - $refund
        ]);
    }
}
