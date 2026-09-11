# TLL Commerce Inventory Ledger Contract

Version: 1.0.0
Date: 2026-09-11
Status: DESIGN

## 1. Core Principle

Inventory is an immutable event log, not a mutable number.

- Truth = SUM(quantity_delta) from ledger
- stock fields = cached snapshot (for performance)
- Every change must be traceable

## 2. Schema

| Field | Type | Purpose |
|-------|------|---------|
| id | bigint | Primary key |
| sku_id | bigint | SKU identity (UNIQUE constraint in P2-01) |
| product_id | bigint | Product for quick lookup |
| event_type | varchar(20) | PURCHASE / SALE / REFUND / ADJUSTMENT / RESERVE / RELEASE / INITIALIZE |
| quantity_delta | int | Positive=inbound, Negative=outbound |
| before_quantity | int | Stock before this event (for audit) |
| after_quantity | int | Stock after this event (for audit) |
| reference_type | varchar(20) | ORDER / REFUND / PURCHASE / MANUAL / SYSTEM |
| reference_id | bigint | Related record ID |
| idempotency_key | varchar(100) | Unique key to prevent duplicate events |
| operator_id | bigint | Who made this change |
| created_at | timestamp | When |

## 3. Event Types

| Event Type | Direction | Purpose |
|------------|-----------|---------|
| INITIALIZE | +N | Genesis snapshot from migration |
| PURCHASE | +N | Inbound stock from supplier |
| RESERVE | -N | Lock stock when order placed |
| SALE | -N | Confirm stock deduction after payment |
| RELEASE | +N | Unlock reserved stock (order cancelled) |
| REFUND | +N | Restore stock after refund success |
| ADJUSTMENT | +/-N | Manual correction |

## 4. Stock State Model

`
AVAILABLE = SUM(INITIALIZE + PURCHASE + REFUND + RELEASE + ADJUSTMENT)
          - SUM(SALE + RESERVE)

RESERVED = SUM(RESERVE) - SUM(RELEASE)
SOLD = SUM(SALE) - SUM(REFUND)
`

Three stock dimensions:
- available: can be purchased now
- reserved: locked by pending orders
- sold: already sold (reduced from available)

## 5. Idempotency Rule

- idempotency_key MUST be unique
- Format: {reference_type}_{reference_id}_{event_type}
- Example: ORDER_123_SALE
- Duplicate insert = silent no-op (not error)

## 6. Genesis Snapshot Strategy

When migrating from old stock fields:

1. For each SKU with existing stock:
   - Create INITIALIZE event
   - quantity_delta = current product_skus.stock
   - reference_type = SYSTEM
   - reference_id = 0
   - idempotency_key = INITIALIZE_{sku_id}

2. This makes today's stock = starting point

## 7. State Machine

`
Order Placed
  ↓
RESERVE (-N)
  ↓
AVAILABLE decreases, RESERVED increases
  ↓
Payment Success
  ↓
SALE (-N)
  ↓
RESERVED decreases, SOLD increases
  ↓
Refund Success
  ↓
REFUND (+N)
  ↓
SOLD decreases, AVAILABLE increases

Order Cancelled (before payment)
  ↓
RELEASE (+N)
  ↓
RESERVED decreases, AVAILABLE increases
`

## 8. Constraints

- UNIQUE(idempotency_key)
- before_quantity + quantity_delta = after_quantity
- after_quantity >= 0 (unless OVERSOLD enabled)
- SKU must exist

## 9. Query Interface

`php
// Get available stock for SKU
InventoryService::getAvailableStock(int ): int

// Get reserved stock
InventoryService::getReservedStock(int ): int

// Get sold stock
InventoryService::getSoldStock(int ): int
`

## 10. Migration Phases

Phase A: Create ledger table + Genesis snapshot
Phase B: Dual-write (old stock + new ledger)
Phase C: Ledger as truth, stock as cache
Phase D: Remove old stock mutation code (future)

## 11. Payment Isolation

Inventory operations MUST NOT block payment.

- Payment success → Inventory SALE event (async or after commit)
- Inventory failure → Log error, do NOT fail payment
- Recovery: Daily reconciliation between ledger and stock

