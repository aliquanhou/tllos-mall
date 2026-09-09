<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("products", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("merchant_id")->default(0)->comment("商户ID，0为自营");
            $table->unsignedBigInteger("category_id");
            $table->unsignedBigInteger("brand_id")->default(0);
            $table->string("name", 200);
            $table->string("subtitle", 255)->nullable();
            $table->string("main_image", 255);
            $table->json("images")->nullable()->comment("商品图片数组");
            $table->text("description")->nullable()->comment("商品详情（富文本）");
            $table->decimal("price", 10, 2)->comment("销售价");
            $table->decimal("market_price", 10, 2)->nullable()->comment("市场价");
            $table->decimal("cost_price", 10, 2)->nullable()->comment("成本价");
            $table->integer("stock")->default(0)->comment("库存");
            $table->integer("sales")->default(0)->comment("销量");
            $table->integer("views")->default(0)->comment("浏览量");
            $table->integer("favorites")->default(0)->comment("收藏数");
            $table->tinyInteger("is_sku")->default(0)->comment("是否多规格");
            $table->string("unit", 20)->default("件");
            $table->decimal("weight", 10, 2)->default(0)->comment("重量kg");
            $table->tinyInteger("is_free_shipping")->default(0);
            $table->decimal("shipping_fee", 10, 2)->default(0);
            $table->tinyInteger("is_new")->default(0);
            $table->tinyInteger("is_hot")->default(0);
            $table->tinyInteger("is_recommend")->default(0);
            $table->tinyInteger("status")->default(1)->comment("1上架 0下架");
            $table->timestamp("on_sale_at")->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(["category_id", "status"]);
            $table->index(["merchant_id", "status"]);
            $table->index("sales");
        });
    }
    public function down(): void { Schema::dropIfExists("products"); }
};
