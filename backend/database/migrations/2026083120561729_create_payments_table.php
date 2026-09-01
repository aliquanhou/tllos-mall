<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("payments", function (Blueprint $table) {
            $table->id();
            $table->string("payment_no", 50)->unique();
            $table->string("order_no", 50);
            $table->unsignedBigInteger("user_id");
            $table->tinyInteger("type")->comment("1订单支付 2充值 3提现");
            $table->tinyInteger("pay_type")->comment("1微信 2支付宝 3余额 4银行卡");
            $table->decimal("amount", 12, 2);
            $table->string("third_payment_no", 100)->nullable();
            $table->tinyInteger("status")->default(0)->comment("0待支付 1已支付 2已关闭 3已退款");
            $table->timestamp("pay_time")->nullable();
            $table->string("remark", 255)->nullable();
            $table->timestamps();
            $table->index(["order_no", "status"]);
            $table->index(["user_id", "created_at"]);
        });
    }
    public function down(): void { Schema::dropIfExists("payments"); }
};
