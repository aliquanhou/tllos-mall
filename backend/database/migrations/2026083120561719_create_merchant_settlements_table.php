<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("merchant_settlements", function (Blueprint $table) {
            $table->id();
            $table->string("settlement_no", 50)->unique();
            $table->unsignedBigInteger("merchant_id");
            $table->decimal("order_amount", 12, 2)->default(0)->comment("订单总额");
            $table->integer("order_count")->default(0);
            $table->decimal("commission", 12, 2)->default(0)->comment("平台佣金");
            $table->decimal("refund_amount", 12, 2)->default(0);
            $table->decimal("settlement_amount", 12, 2)->comment("结算金额");
            $table->timestamp("start_date");
            $table->timestamp("end_date");
            $table->tinyInteger("status")->default(0)->comment("0待结算 1已结算 2已拒绝");
            $table->timestamp("settled_at")->nullable();
            $table->string("remark", 255)->nullable();
            $table->timestamps();
            $table->index(["merchant_id", "status"]);
        });
    }
    public function down(): void { Schema::dropIfExists("merchant_settlements"); }
};
