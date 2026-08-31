<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("express_companies", function (Blueprint $table) {
            $table->id();
            $table->string("name", 50)->unique();
            $table->string("code", 30)->unique()->comment("快递鸟/快递100编码");
            $table->string("logo", 255)->nullable();
            $table->string("phone", 20)->nullable();
            $table->string("website", 255)->nullable();
            $table->integer("sort")->default(0);
            $table->tinyInteger("status")->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("express_companies"); }
};
