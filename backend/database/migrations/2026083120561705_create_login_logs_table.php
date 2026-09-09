<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("login_logs", function (Blueprint $table) {
            $table->id();
            $table->string("username", 50)->nullable();
            $table->string("ip", 50)->nullable();
            $table->string("user_agent", 500)->nullable();
            $table->tinyInteger("status")->default(1)->comment("1成功 0失败");
            $table->string("message", 255)->nullable();
            $table->string("type", 20)->default("admin")->comment("admin/user/merchant");
            $table->timestamp("created_at")->useCurrent();
            $table->index(["type", "created_at"]);
        });
    }
    public function down(): void { Schema::dropIfExists("login_logs"); }
};
