<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("user_balance_logs", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->tinyInteger("type")->comment("1充值 2消费 3退款 4提现 5系统调整");
            $table->decimal("amount", 12, 2)->comment("变动金额，正数增加负数减少");
            $table->decimal("balance_before", 12, 2);
            $table->decimal("balance_after", 12, 2);
            $table->string("order_no", 50)->nullable();
            $table->string("remark", 255)->nullable();
            $table->timestamp("created_at")->useCurrent();
            $table->index(["user_id", "created_at"]);
        });
    }
    public function down(): void { Schema::dropIfExists("user_balance_logs"); }
};
