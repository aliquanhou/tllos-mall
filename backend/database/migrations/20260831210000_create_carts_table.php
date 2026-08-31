<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('sku_id')->default(0);
            $table->integer('quantity')->default(1);
            $table->tinyInteger('selected')->default(1);
            $table->timestamps();
            $table->index('user_id');
            $table->unique(['user_id', 'product_id', 'sku_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('carts'); }
};
