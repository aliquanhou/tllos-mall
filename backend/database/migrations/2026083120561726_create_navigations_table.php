<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("navigations", function (Blueprint $table) {
            $table->id();
            $table->string("position", 50)->default("home")->comment("位置");
            $table->string("name", 50);
            $table->string("icon", 255)->nullable();
            $table->string("link_type", 20)->default("none");
            $table->string("link_value", 255)->nullable();
            $table->integer("sort")->default(0);
            $table->tinyInteger("status")->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("navigations"); }
};
