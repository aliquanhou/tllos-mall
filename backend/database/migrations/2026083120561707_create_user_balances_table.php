<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("user_balances", function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_id")->unique();
            $table->decimal("balance", 12, 2)->default(0)->comment("可用余额");
            $table->decimal("frozen", 12, 2)->default(0)->comment("冻结金额");
            $table->decimal("total_recharge", 12, 2)->default(0);
            $table->decimal("total_consume", 12, 2)->default(0);
            $table->integer("points")->default(0)->comment("积分");
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("user_balances"); }
};
