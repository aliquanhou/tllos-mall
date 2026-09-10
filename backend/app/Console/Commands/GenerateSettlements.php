<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class GenerateSettlements extends Command
{
    protected $signature = 'settlement:generate {--period=weekly : 结算周期 weekly|monthly} {--merchant_id= : 指定商家ID}';
    protected $description = '生成商家结算单';

    public function handle()
    {
        $period = $this->option('period');
        $merchantId = $this->option('merchant_id');

        if ($period == 'weekly') {
            $startDate = Carbon::now()->startOfWeek()->subWeek();
            $endDate = Carbon::now()->startOfWeek();
        } else {
            $startDate = Carbon::now()->startOfMonth()->subMonth();
            $endDate = Carbon::now()->startOfMonth();
        }

        $this->info("结算周期: {$startDate->format('Y-m-d')} ~ {$endDate->format('Y-m-d')}");

        $merchants = DB::table('merchants')->where('status', 1);
        if ($merchantId) {
            $merchants->where('id', $merchantId);
        }
        $merchants = $merchants->get();

        $count = 0;
        foreach ($merchants as $merchant) {
            $settlementNo = 'STL' . date('YmdHis') . str_pad($merchant->id, 4, '0', STR_PAD_LEFT);

            $orders = DB::table('orders')
                ->where('merchant_id', $merchant->id)
                ->where('status', '>=', 3)
                ->where('confirm_time', '>=', $startDate)
                ->where('confirm_time', '<', $endDate)
                ->get();

            $orderAmount = $orders->sum('pay_amount');
            $orderCount = $orders->count();
            $commission = $orders->sum('commission');

            $refundAmount = DB::table('refunds')
                ->where('merchant_id', $merchant->id)
                ->where('status', 1)
                ->where('created_at', '>=', $startDate)
                ->where('created_at', '<', $endDate)
                ->sum('refund_amount');

            $settlementAmount = round($orderAmount - $commission - $refundAmount, 2);

            if ($orderCount == 0 && $refundAmount == 0) {
                continue;
            }

            $exists = DB::table('merchant_settlements')
                ->where('merchant_id', $merchant->id)
                ->where('start_date', $startDate)
                ->where('end_date', $endDate)
                ->first();

            if ($exists) {
                $this->warn("商家 {$merchant->name} 结算单已存在，跳过");
                continue;
            }

            DB::table('merchant_settlements')->insert([
                'settlement_no' => $settlementNo,
                'merchant_id' => $merchant->id,
                'order_amount' => $orderAmount,
                'order_count' => $orderCount,
                'commission' => $commission,
                'refund_amount' => $refundAmount,
                'settlement_amount' => $settlementAmount,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 0,
                'remark' => "系统自动生成{$period}结算单",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $count++;
            $this->info("生成结算单: {$settlementNo} 商家: {$merchant->name} 金额: ¥{$settlementAmount}");
        }

        $this->info("共生成 {$count} 份结算单");
        return 0;
    }
}
