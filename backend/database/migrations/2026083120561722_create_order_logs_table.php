<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("order_logs", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("order_id");
            $table->string("order_no", 50);
            $table->tinyInteger("action")->comment("1创建 2支付 3发货 4收货 5取消 6退款 7评论");
            $table->string("action_name", 50);
            $table->string("operator_type", 20)->default("user")->comment("user/admin/merchant/system");
            $table->unsignedBigInteger("operator_id")->default(0);
            $table->string("remark", 500)->nullable();
            $table->timestamp("created_at")->useCurrent();
            $table->index("order_id");
        });
    }
    public function down(): void { Schema::dropIfExists("order_logs"); }
};
