<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-REFUND-FOUNDATION-R1: Add UNIQUE(provider, provider_refund_no)
 *
 * Rationale: Different payment providers (alipay/wechat) may use
 * overlapping refund number spaces. Therefore uniqueness must be
 * scoped per-provider, not global.
 *
 * MySQL UNIQUE allows multiple NULL values, so records with
 * provider_refund_no = NULL (not yet called third-party) do not conflict.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_refunds', function (Blueprint $table) {
            // Drop old non-unique index
            $table->dropIndex('order_refunds_provider_refund_no_index');

            // Add composite unique: same provider cannot reuse refund number
            $table->unique(['provider', 'provider_refund_no'], 'order_refunds_provider_refund_no_unique');
        });
    }

    public function down(): void
    {
        Schema::table('order_refunds', function (Blueprint $table) {
            $table->dropUnique('order_refunds_provider_refund_no_unique');
            $table->index('provider_refund_no', 'order_refunds_provider_refund_no_index');
        });
    }
};
