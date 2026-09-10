<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("coupons", function (Blueprint $table) {
            $table->id();
            $table->string("name", 100);
            $table->tinyInteger("type")->default(1)->comment("1满减券 2折扣券 3无门槛券");
            $table->decimal("min_amount", 10, 2)->default(0)->comment("使用门槛");
            $table->decimal("discount_amount", 10, 2)->default(0)->comment("优惠金额");
            $table->decimal("discount_rate", 5, 2)->nullable()->comment("折扣率，如8.5表示8.5折");
            $table->integer("total_count")->default(0)->comment("发放总量，0不限");
            $table->integer("used_count")->default(0);
            $table->integer("receive_count")->default(0);
            $table->integer("limit_per_user")->default(1)->comment("每人限领");
            $table->tinyInteger("validity_type")->default(1)->comment("1固定时间 2领取后N天");
            $table->timestamp("start_time")->nullable();
            $table->timestamp("end_time")->nullable();
            $table->integer("valid_days")->default(0)->comment("领取后有效天数");
            $table->json("适用范围")->nullable()->comment("适用商品范围JSON");
            $table->tinyInteger("is_new_user")->default(0)->comment("仅新用户");
            $table->tinyInteger("status")->default(1)->comment("1正常 0关闭");
            $table->integer("sort")->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("coupons"); }
};
