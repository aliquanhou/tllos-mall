<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("product_categories", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("parent_id")->default(0);
            $table->string("name", 50);
            $table->string("icon", 255)->nullable();
            $table->string("image", 255)->nullable();
            $table->string("description", 255)->nullable();
            $table->tinyInteger("level")->default(1);
            $table->integer("sort")->default(0);
            $table->tinyInteger("status")->default(1);
            $table->timestamps();
            $table->index("parent_id");
        });
    }
    public function down(): void { Schema::dropIfExists("product_categories"); }
};
