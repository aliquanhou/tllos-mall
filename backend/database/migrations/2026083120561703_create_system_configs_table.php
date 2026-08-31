<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("system_configs", function (Blueprint $table) {
            $table->id();
            $table->string("group", 50)->default("basic")->comment("配置分组");
            $table->string("key", 100)->unique();
            $table->string("name", 100)->comment("配置名称");
            $table->text("value")->nullable()->comment("配置值");
            $table->string("type", 20)->default("text")->comment("text/textarea/number/image/switch/json");
            $table->string("options", 500)->nullable()->comment("选项JSON");
            $table->integer("sort")->default(0);
            $table->tinyInteger("status")->default(1);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("system_configs"); }
};
