<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("seckill_products", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("seckill_id");
            $table->unsignedBigInteger("product_id");
            $table->unsignedBigInteger("sku_id")->default(0);
            $table->decimal("seckill_price", 10, 2);
            $table->integer("seckill_stock");
            $table->integer("sold_count")->default(0);
            $table->integer("limit_per_user")->default(1);
            $table->integer("sort")->default(0);
            $table->tinyInteger("status")->default(1);
            $table->timestamps();
            $table->index(["seckill_id", "status"]);
        });
    }
    public function down(): void { Schema::dropIfExists("seckill_products"); }
};
