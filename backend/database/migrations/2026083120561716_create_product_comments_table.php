<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("product_comments", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("product_id");
            $table->unsignedBigInteger("user_id");
            $table->unsignedBigInteger("order_id");
            $table->unsignedBigInteger("order_item_id");
            $table->tinyInteger("rating")->default(5)->comment("1-5星");
            $table->string("content", 1000)->nullable();
            $table->json("images")->nullable();
            $table->string("reply", 500)->nullable()->comment("商家回复");
            $table->timestamp("reply_at")->nullable();
            $table->tinyInteger("is_anonymous")->default(0);
            $table->tinyInteger("status")->default(1)->comment("1显示 0隐藏");
            $table->timestamps();
            $table->index(["product_id", "status"]);
        });
    }
    public function down(): void { Schema::dropIfExists("product_comments"); }
};
