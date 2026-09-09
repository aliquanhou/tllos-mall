<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EvaluateUserLevels extends Command
{
    protected $signature = 'user:evaluate-levels';
    protected $description = '评估用户等级，根据积分自动升降级';

    public function handle()
    {
        $this->info('开始评估用户等级...');

        $levels = DB::table('user_levels')
            ->where('status', 1)
            ->orderBy('level', 'asc')
            ->get();

        if ($levels->isEmpty()) {
            $this->error('没有可用的用户等级');
            return 1;
        }

        $defaultLevel = $levels->firstWhere('is_default', 1) ?? $levels->first();

        $users = DB::table('users')
            ->where('status', 1)
            ->get();

        $upgraded = 0;
        $downgraded = 0;
        $unchanged = 0;

        foreach ($users as $user) {
            $currentPoints = $user->points ?? 0;
            $currentLevelId = $user->level_id ?? $defaultLevel->id;

            $targetLevel = $defaultLevel;
            foreach ($levels as $level) {
                if ($currentPoints >= $level->upgrade_points) {
                    $targetLevel = $level;
                }
            }

            if ($targetLevel->id != $currentLevelId) {
                $oldLevel = $levels->firstWhere('id', $currentLevelId) ?? $defaultLevel;
                $reason = ($targetLevel->level > $oldLevel->level) ? 'auto_upgrade' : 'auto_downgrade';

                DB::table('users')->where('id', $user->id)->update([
                    'level_id' => $targetLevel->id,
                    'updated_at' => now(),
                ]);

                DB::table('user_level_logs')->insert([
                    'user_id' => $user->id,
                    'old_level_id' => $currentLevelId,
                    'new_level_id' => $targetLevel->id,
                    'reason' => $reason,
                    'points_before' => $currentPoints,
                    'points_after' => $currentPoints,
                    'remark' => "积分{$currentPoints}，自动从{$oldLevel->name}变更为{$targetLevel->name}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($reason == 'auto_upgrade') {
                    DB::table('user_notifications')->insert([
                        'user_id' => $user->id,
                        'title' => '等级升级通知',
                        'content' => "恭喜您！您的会员等级已从【{$oldLevel->name}】升级为【{$targetLevel->name}】，享受更多专属权益。",
                        'type' => 'level',
                        'is_read' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $upgraded++;
                } else {
                    $downgraded++;
                }

                $this->line("用户ID:{$user->id} {$oldLevel->name} -> {$targetLevel->name} ({$reason})");
            } else {
                $unchanged++;
            }
        }

        $this->info("评估完成：升级{$upgraded}人，降级{$downgraded}人，不变{$unchanged}人");
        return 0;
    }
}
