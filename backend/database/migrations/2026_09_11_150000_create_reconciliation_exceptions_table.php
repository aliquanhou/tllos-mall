<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1-PI-06: Create reconciliation_exceptions table
 *
 * Records discrepancies between local records and provider records.
 * Principle: Detect first, resolve manually. Never auto-fix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliation_exceptions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['PAYMENT', 'REFUND'])->index();
            $table->string('identity_type', 50); // payment_no / refund_no
            $table->string('identity_value', 100)->index();
            $table->string('local_status', 50)->nullable();
            $table->string('provider_status', 50)->nullable();
            $table->string('difference_code', 100)->index();
            $table->text('difference_detail')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by', 100)->nullable();
            $table->timestamps();

            $table->index(['type', 'difference_code']);
            $table->index(['type', 'identity_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_exceptions');
    }
};
