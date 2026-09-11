<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * P1-REFUND-PROVIDER-PRECONDITION: Payment Identity
 *
 * Add provider field to payments table and establish
 * UNIQUE(provider, third_payment_no) as the canonical
 * Provider Transaction Identity.
 *
 * pay_type mapping:
 *   1 = wechat
 *   2 = alipay
 *   3 = balance
 *   4 = points
 *
 * Existing records are backfilled with provider based on pay_type.
 * Row data is NOT changed except adding the provider column value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider', 20)->nullable()->after('pay_type')->comment('Payment provider: wechat/alipay/balance/points');
            $table->index('provider', 'payments_provider_index');
        });

        // Backfill provider from pay_type for existing records
        DB::statement("UPDATE payments SET provider = CASE pay_type
            WHEN 1 THEN 'wechat'
            WHEN 2 THEN 'alipay'
            WHEN 3 THEN 'balance'
            WHEN 4 THEN 'points'
            ELSE NULL
        END WHERE provider IS NULL");

        // Add composite unique: same provider cannot reuse transaction id
        // MySQL UNIQUE allows multiple NULLs, so pending payments (third_payment_no=NULL) don't conflict
        Schema::table('payments', function (Blueprint $table) {
            $table->unique(['provider', 'third_payment_no'], 'payments_provider_third_payment_no_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_provider_third_payment_no_unique');
            $table->dropIndex('payments_provider_index');
            $table->dropColumn('provider');
        });
    }
};
