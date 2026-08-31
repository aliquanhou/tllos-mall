<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("order_refunds", function (Blueprint $table) {
            $table->id();
            $table->string("refund_no", 50)->unique();
            $table->unsignedBigInteger("order_id");
            $table->unsignedBigInteger("order_item_id")->default(0);
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("merchant_id")->default(0);
            $table->tinyInteger("type")->default(1)->comment("1仅退款 2退货退款");
            $table->decimal("refund_amount", 12, 2);
            $table->decimal("shipping_fee", 10, 2)->default(0);
            $table->string("reason", 255);
            $table->text("description")->nullable();
            $table->json("images")->nullable();
            $table->tinyInteger("status")->default(0)->comment("0待审核 1商家同意 2商家拒绝 3买家退货 4商家收货 5退款成功 6已关闭");
            $table->string("refuse_reason", 255)->nullable();
            $table->string("express_company", 50)->nullable();
            $table->string("express_no", 50)->nullable();
            $table->timestamp("ship_time")->nullable();
            $table->timestamp("receive_time")->nullable();
            $table->timestamp("refund_time")->nullable();
            $table->string("refund_no_third", 100)->nullable();
            $table->timestamps();
            $table->index(["user_id", "status"]);
            $table->index(["merchant_id", "status"]);
        });
    }
    public function down(): void { Schema::dropIfExists("order_refunds"); }
};
