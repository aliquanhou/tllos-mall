<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("user_addresses", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id");
            $table->string("name", 50)->comment("收货人");
            $table->string("mobile", 20);
            $table->unsignedBigInteger("province_id");
            $table->unsignedBigInteger("city_id");
            $table->unsignedBigInteger("district_id");
            $table->string("province_name", 50);
            $table->string("city_name", 50);
            $table->string("district_name", 50);
            $table->string("detail", 255)->comment("详细地址");
            $table->string("postal_code", 10)->nullable();
            $table->tinyInteger("is_default")->default(0);
            $table->timestamps();
            $table->index("user_id");
        });
    }
    public function down(): void { Schema::dropIfExists("user_addresses"); }
};
