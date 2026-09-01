<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("orders", function (Blueprint $table) {
            $table->id();
            $table->string("order_no", 50)->unique();
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("merchant_id")->default(0);
            $table->decimal("total_amount", 12, 2)->comment("商品总额");
            $table->decimal("shipping_fee", 10, 2)->default(0);
            $table->decimal("discount_amount", 10, 2)->default(0)->comment("优惠金额");
            $table->decimal("coupon_amount", 10, 2)->default(0);
            $table->decimal("points_amount", 10, 2)->default(0);
            $table->decimal("pay_amount", 12, 2)->comment("实付金额");
            $table->decimal("cost_amount", 12, 2)->default(0)->comment("成本金额");
            $table->decimal("commission", 12, 2)->default(0)->comment("平台佣金");
            $table->decimal("merchant_amount", 12, 2)->default(0)->comment("商家所得");
            $table->tinyInteger("pay_type")->default(0)->comment("0未支付 1微信 2支付宝 3余额 4银行卡");
            $table->string("pay_no", 100)->nullable()->comment("第三方支付单号");
            $table->timestamp("pay_time")->nullable();
            $table->tinyInteger("order_type")->default(1)->comment("1普通 2秒杀 3拼团");
            $table->tinyInteger("status")->default(0)->comment("0待支付 1待发货 2待收货 3已完成 4已取消 5退款中 6已退款");
            $table->string("receiver_name", 50);
            $table->string("receiver_mobile", 20);
            $table->unsignedBigInteger("province_id");
            $table->unsignedBigInteger("city_id");
            $table->unsignedBigInteger("district_id");
            $table->string("province_name", 50);
            $table->string("city_name", 50);
            $table->string("district_name", 50);
            $table->string("receiver_address", 255);
            $table->string("express_company", 50)->nullable();
            $table->string("express_no", 50)->nullable();
            $table->timestamp("ship_time")->nullable();
            $table->timestamp("confirm_time")->nullable();
            $table->string("user_remark", 255)->nullable();
            $table->string("admin_remark", 255)->nullable();
            $table->unsignedBigInteger("coupon_id")->default(0);
            $table->integer("use_points")->default(0);
            $table->integer("earn_points")->default(0);
            $table->timestamp("auto_cancel_at")->nullable();
            $table->timestamp("auto_confirm_at")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(["user_id", "status"]);
            $table->index(["merchant_id", "status"]);
            $table->index("order_no");
            $table->index("created_at");
        });
    }
    public function down(): void { Schema::dropIfExists("orders"); }
};
