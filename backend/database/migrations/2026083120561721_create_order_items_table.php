<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("order_items", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("order_id");
            $table->string("order_no", 50);
            $table->unsignedBigInteger("product_id");
            $table->unsignedBigInteger("sku_id")->default(0);
            $table->string("product_name", 200);
            $table->string("product_image", 255);
            $table->string("sku_text", 255)->nullable();
            $table->decimal("price", 10, 2);
            $table->decimal("market_price", 10, 2)->nullable();
            $table->decimal("cost_price", 10, 2)->nullable();
            $table->integer("quantity");
            $table->decimal("total_amount", 12, 2);
            $table->decimal("discount_amount", 10, 2)->default(0);
            $table->decimal("pay_amount", 12, 2);
            $table->tinyInteger("is_commented")->default(0);
            $table->tinyInteger("is_refunded")->default(0);
            $table->timestamps();
            $table->index("order_id");
            $table->index("product_id");
        });
    }
    public function down(): void { Schema::dropIfExists("order_items"); }
};
