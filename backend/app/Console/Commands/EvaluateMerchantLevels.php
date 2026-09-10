<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EvaluateMerchantLevels extends Command
{
    protected $signature = 'merchant:evaluate-levels';
    protected $description = '评估商家等级，自动升降级';

    public function handle()
    {
        $this->info('开始评估商家等级...');

        $levels = DB::table('merchant_levels')
            ->where('status', 1)
            ->orderBy('sort', 'asc')
            ->get();

        if ($levels->isEmpty()) {
            $this->error('没有可用的商家等级');
            return 1;
        }

        $merchants = DB::table('merchants')
            ->where('status', 1)
            ->get();

        $upgraded = 0;
        $downgraded = 0;

        foreach ($merchants as $merchant) {
            // 计算商家统计数据
            $stats = DB::table('orders')
                ->where('merchant_id', $merchant->id)
                ->whereIn('status', [3]) // 已完成订单
                ->select(
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('COALESCE(SUM(pay_amount), 0) as total_gmv')
                )
                ->first();

            $productCount = DB::table('products')
                ->where('merchant_id', $merchant->id)
                ->where('status', 1)
                ->count();

            $totalOrders = $stats->total_orders ?? 0;
            $totalGmv = $stats->total_gmv ?? 0;

            // 找到商家满足的最高等级
            $targetLevel = $levels->first(); // 默认最低等级
            foreach ($levels as $level) {
                $meets = true;
                if ($level->min_orders > 0 && $totalOrders < $level->min_orders) $meets = false;
                if ($level->min_gmv > 0 && $totalGmv < $level->min_gmv) $meets = false;
                if ($level->min_products > 0 && $productCount < $level->min_products) $meets = false;
                if ($meets) {
                    $targetLevel = $level;
                }
            }

            if ($merchant->level != $targetLevel->id) {
                $oldLevel = $merchant->level;
                $reason = ($targetLevel->id > $oldLevel) ? 'auto_upgrade' : 'auto_downgrade';

                // 检查保护期（升级后保护期内不降级）
                if ($reason == 'auto_downgrade') {
                    $lastUpgrade = DB::table('merchant_level_logs')
                        ->where('merchant_id', $merchant->id)
                        ->where('reason', 'auto_upgrade')
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($lastUpgrade) {
                        $protectionDays = $targetLevel->protection_period_days ?? 30;
                        $upgradeDate = strtotime($lastUpgrade->created_at);
                        if (time() - $upgradeDate < $protectionDays * 86400) {
                            $this->info("商家{$merchant->id}在保护期内，跳过降级");
                            continue;
                        }
                    }
                }

                DB::table('merchants')->where('id', $merchant->id)->update([
                    'level' => $targetLevel->id,
                    'updated_at' => now()
                ]);

                DB::table('merchant_level_logs')->insert([
                    'merchant_id' => $merchant->id,
                    'old_level_id' => $oldLevel,
                    'new_level_id' => $targetLevel->id,
                    'reason' => $reason,
                    'remark' => "订单:{$totalOrders}, GMV:{$totalGmv}, 商品:{$productCount}",
                    'created_at' => now()
                ]);

                // 发送通知
                DB::table('merchant_notifications')->insert([
                    'merchant_id' => $merchant->id,
                    'type' => $reason == 'auto_upgrade' ? 'level_up' : 'level_down',
                    'title' => $reason == 'auto_upgrade' ? '等级升级通知' : '等级降级通知',
                    'content' => "您的商家等级已变更为：{$targetLevel->name}",
                    'is_read' => 0,
                    'created_at' => now()
                ]);

                if ($reason == 'auto_upgrade') $upgraded++;
                else $downgraded++;

                $this->info("商家{$merchant->id}: {$oldLevel} -> {$targetLevel->id} ({$reason})");
            }
        }

        $this->info("评估完成：升级{$upgraded}个，降级{$downgraded}个");
        return 0;
    }
}
