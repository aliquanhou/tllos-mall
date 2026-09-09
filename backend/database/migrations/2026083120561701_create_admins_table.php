<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("admins", function (Blueprint $table) {
            $table->id();
            $table->string("username", 50)->unique();
            $table->string("password");
            $table->string("nickname", 50)->nullable();
            $table->string("avatar", 255)->nullable();
            $table->string("mobile", 20)->nullable();
            $table->string("email", 100)->nullable();
            $table->unsignedBigInteger("role_id")->default(0);
            $table->tinyInteger("status")->default(1)->comment("1正常 0禁用");
            $table->timestamp("last_login_at")->nullable();
            $table->string("last_login_ip", 50)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists("admins"); }
};
