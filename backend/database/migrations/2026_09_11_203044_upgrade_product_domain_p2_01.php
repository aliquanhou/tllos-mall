<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Task 1: SKU Identity Hardening - UNIQUE(sku_no) already exists
        // Verified: product_skus_sku_no_unique exists with Non_unique=0

        // Task 2: Product Agent Metadata
        if (!Schema::hasColumn('products', 'agent_metadata')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('agent_metadata')->nullable()->after('detail');
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('agent_metadata');
        });
    }
};
