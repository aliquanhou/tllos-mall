<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("product_attributes", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("category_id")->default(0);
            $table->string("name", 50)->comment("属性名，如颜色、尺码");
            $table->tinyInteger("type")->default(1)->comment("1单选 2多选 3输入");
            $table->tinyInteger("is_spec")->default(0)->comment("是否规格属性（影响SKU）");
            $table->json("values")->nullable()->comment("可选值JSON");
            $table->integer("sort")->default(0);
            $table->tinyInteger("status")->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("product_attributes"); }
};
