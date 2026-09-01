<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("seckills", function (Blueprint $table) {
            $table->id();
            $table->string("name", 100);
            $table->timestamp("start_time");
            $table->timestamp("end_time");
            $table->tinyInteger("status")->default(1)->comment("1进行中 0未开始 2已结束");
            $table->integer("sort")->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists("seckills"); }
};
