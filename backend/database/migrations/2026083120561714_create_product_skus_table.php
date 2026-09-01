<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("product_skus", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("product_id");
            $table->string("sku_no", 50)->unique()->comment("SKU编码");
            $table->json("specs")->nullable()->comment("规格组合JSON {颜色:红色,尺码:L}");
            $table->string("spec_text", 255)->nullable()->comment("规格文本");
            $table->decimal("price", 10, 2);
            $table->decimal("market_price", 10, 2)->nullable();
            $table->decimal("cost_price", 10, 2)->nullable();
            $table->integer("stock")->default(0);
            $table->integer("sales")->default(0);
            $table->string("image", 255)->nullable();
            $table->tinyInteger("status")->default(1);
            $table->timestamps();
            $table->index("product_id");
        });
    }
    public function down(): void { Schema::dropIfExists("product_skus"); }
};
