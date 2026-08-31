<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("banners", function (Blueprint $table) {
            $table->id();
            $table->string("position", 50)->default("home")->comment("位置标识");
            $table->string("title", 100)->nullable();
            $table->string("image", 255);
            $table->string("link_type", 20)->default("none")->comment("none/product/category/url");
            $table->string("link_value", 255)->nullable();
            $table->integer("sort")->default(0);
            $table->tinyInteger("status")->default(1);
            $table->timestamp("start_time")->nullable();
            $table->timestamp("end_time")->nullable();
            $table->timestamps();
            $table->index(["position", "status"]);
        });
    }
    public function down(): void { Schema::dropIfExists("banners"); }
};
