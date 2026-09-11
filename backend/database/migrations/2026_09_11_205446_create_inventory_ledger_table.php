<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_ledger', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sku_id')->index();
            $table->bigInteger('product_id')->index();
            $table->string('event_type', 20); // INITIALIZE, PURCHASE, SALE, REFUND, RESERVE, RELEASE, ADJUSTMENT
            $table->integer('quantity_delta'); // positive=inbound, negative=outbound
            $table->integer('before_quantity')->default(0);
            $table->integer('after_quantity')->default(0);
            $table->string('reference_type', 20)->index(); // ORDER, REFUND, PURCHASE, MANUAL, SYSTEM
            $table->bigInteger('reference_id')->default(0)->index();
            $table->string('idempotency_key', 100)->unique();
            $table->bigInteger('operator_id')->default(0);
            $table->bigInteger('source_version')->default(0); // for future multi-source sync
            $table->json('metadata')->nullable(); // reason, batch, agent info
            $table->timestamp('created_at')->nullable();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['sku_id', 'event_type']);
            $table->index('created_at');
        });

        // Genesis Snapshot: create INITIALIZE event for each SKU
        $skus = DB::table('product_skus')->get(['id', 'product_id', 'stock']);
        $now = now();

        foreach ($skus as $sku) {
            $idempotencyKey = 'INITIALIZE_' . $sku->id;

            // Skip if already exists (idempotent migration)
            $exists = DB::table('inventory_ledger')
                ->where('idempotency_key', $idempotencyKey)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('inventory_ledger')->insert([
                'sku_id' => $sku->id,
                'product_id' => $sku->product_id,
                'event_type' => 'INITIALIZE',
                'quantity_delta' => $sku->stock,
                'before_quantity' => 0,
                'after_quantity' => $sku->stock,
                'reference_type' => 'SYSTEM_MIGRATION',
                'reference_id' => 20260911,
                'idempotency_key' => $idempotencyKey,
                'operator_id' => 0,
                'source_version' => 0,
                'metadata' => json_encode(['reason' => 'genesis_snapshot', 'migration_date' => '2026-09-11']),
                'created_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_ledger');
    }
};
