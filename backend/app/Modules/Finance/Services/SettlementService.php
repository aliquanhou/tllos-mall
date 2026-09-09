<?php

namespace App\Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SettlementService
{
    public function calculateOrderSplit($order)
    {
        $platformRate = $this->getPlatformRate();
        $orderAmount = $order->pay_amount;
        $platformCommission = round($orderAmount * $platformRate / 100, 2);
        $merchantAmount = round($orderAmount - $platformCommission, 2);

        return [
            'order_amount' => $orderAmount,
            'platform_commission' => $platformCommission,
            'merchant_amount' => $merchantAmount,
            'platform_rate' => $platformRate,
        ];
    }

    public function processOrderSettlement($order)
    {
        if ($order->status < 3) {
            return false;
        }

        $split = $this->calculateOrderSplit($order);

        DB::beginTransaction();
        try {
            DB::table('orders')->where('id', $order->id)->update([
                'commission' => $split['platform_commission'],
                'merchant_amount' => $split['merchant_amount'],
                'updated_at' => now(),
            ]);

            DB::table('settlement_records')->insert([
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'merchant_id' => $order->merchant_id,
                'order_amount' => $split['order_amount'],
                'platform_commission' => $split['platform_commission'],
                'merchant_amount' => $split['merchant_amount'],
                'status' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('订单分账计算失败', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function getPlatformRate()
    {
        $config = DB::table('system_configs')->where('code', 'platform_commission_rate')->first();
        if ($config && $config->value) {
            return floatval($config->value);
        }
        return 5.0;
    }
}
