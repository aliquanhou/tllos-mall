# TLL Commerce Product Domain Contract

Version: 1.0.0
Date: 2026-09-11
Status: ACTIVE

## 1. Product Identity

- **Primary Key**: products.id
- **Identity**: product_no (future)
- **Immutable**: id, identity
- **Mutable**: name, price, images, status

## 2. SKU Identity

- **Primary Key**: product_skus.id
- **Unique Identity**: product_skus.sku_no (UNIQUE constraint)
- **Relationship**: product_id → products.id
- **Immutable**: id, sku_no
- **Mutable**: price, stock, image, status

## 3. Category Identity

- **Primary Key**: categories.id
- **Tree Structure**: parent_id → categories.id
- **Immutable**: id
- **Mutable**: name, sort, status

## 4. Price Identity

| Field | Table | Purpose |
|-------|-------|---------|
| price | products | Default selling price |
| original_price | products | Original price (display) |
| market_price | products | Market reference price |
| cost_price | products | Cost price (internal) |
| price | product_skus | SKU-level price (overrides product) |

## 5. Availability Identity

- **Current**: products.stock + product_skus.stock (dual fields)
- **Future**: inventory_ledger (truth source)
- **Rule**: Ledger is truth, snapshot is cache

## 6. Agent Metadata Schema

`json
{
   industry: textile,
  application: fabric_production,
  material: cotton,
  machine_type: circular_knitting_machine,
  specification_schema: machine_dimensions,
  certification: CE,
  lead_time: 30days,
  customizable: true
}
`

Purpose: Enable AI Agent reasoning about products.

## 7. State Machine

- **Product Status**: 0=draft, 1=active, 2=offline
- **SKU Status**: 0=disabled, 1=active

## 8. Constraints

- UNIQUE(product_skus.sku_no)
- Soft delete: products.deleted_at
- Order items are snapshot, not live references

