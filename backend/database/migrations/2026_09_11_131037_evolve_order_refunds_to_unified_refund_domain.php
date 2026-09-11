<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-REFUND-FOUNDATION: Evolve order_refunds into unified Refund domain table.
 *
 * Adds Payment identity, Provider identity, and audit fields required for
 * a proper refund state machine. Does NOT modify existing status values or
 * historical data — legacy records remain as-is for reconciliation.
 *
 * New status semantics (code-level constants, no data migration):
 *   0 = REQUESTED   (pending review/processing)
 *   1 = PROCESSING  (third-party accepted, awaiting final result)
 *   2 = SUCCESS     (refund completed)
 *   3 = FAILED      (refund failed, may retry)
 *   4 = CANCELLED   (user cancelled)
 *   5 = UNKNOWN     (timeout/ambiguous, requires manual reconciliation)
 *   6 = REJECTED    (admin rejected)
 *
 * Legacy status mapping (compatibility only, no data change):
 *   old 0 (pending)  -> new 0 REQUESTED
 *   old 2 (rejected) -> new 6 REJECTED
 *   old 5 (success)  -> new 2 SUCCESS (legacy, unverified)
 *   old 6 (cancelled)-> new 4 CANCELLED
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_refunds', function (Blueprint $table) {
            // Payment identity: Order → Payment → Refund
            $table->unsignedBigInteger('payment_id')->nullable()->after('order_item_id');
            $table->string('payment_no', 50)->nullable()->after('payment_id');

            // Provider identity: local refund ↔ third-party refund
            $table->string('provider', 20)->nullable()->after('payment_no')
                ->comment('alipay/wechat/balance/points');
            $table->string('provider_transaction_no', 100)->nullable()->after('provider')
                ->comment('Original third-party transaction id');
            $table->string('provider_refund_no', 100)->nullable()->after('provider_transaction_no')
                ->comment('Third-party refund id, unique per provider');

            // Audit & retry
            $table->string('failure_reason', 255)->nullable()->after('refund_no_third');
            $table->unsignedInteger('attempts')->default(0)->after('failure_reason');
            $table->timestamp('refunded_at')->nullable()->after('attempts');

            // Indexes
            $table->index('payment_id');
            $table->index('payment_no');
            $table->index('provider');
            $table->index('provider_refund_no');
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('order_refunds', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'status']);
            $table->dropIndex(['provider_refund_no']);
            $table->dropIndex(['provider']);
            $table->dropIndex(['payment_no']);
            $table->dropIndex(['payment_id']);

            $table->dropColumn([
                'payment_id', 'payment_no',
                'provider', 'provider_transaction_no', 'provider_refund_no',
                'failure_reason', 'attempts', 'refunded_at',
            ]);
        });
    }
};
