<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("user_coupons", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("coupon_id");
            $table->string("coupon_name", 100);
            $table->decimal("min_amount", 10, 2)->default(0)->comment("使用门槛");
            $table->decimal("discount_amount", 10, 2)->comment("优惠金额");
            $table->tinyInteger("discount_type")->default(1)->comment("1满减 2折扣");
            $table->decimal("discount_rate", 5, 2)->nullable()->comment("折扣率");
            $table->timestamp("start_time");
            $table->timestamp("end_time");
            $table->tinyInteger("status")->default(0)->comment("0未使用 1已使用 2已过期");
            $table->string("order_no", 50)->nullable();
            $table->timestamp("used_time")->nullable();
            $table->timestamps();
            $table->index(["user_id", "status"]);
        });
    }
    public function down(): void { Schema::dropIfExists("user_coupons"); }
};
