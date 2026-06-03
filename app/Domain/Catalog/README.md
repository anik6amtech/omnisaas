# Catalog

> Bounded context: products, variants, stock, delivery rules.

- **Plane:** tenant
- **Epic:** E6

## Responsibilities
- Products with `variants` (JSONB), price, stock, images, description.
- CSV import (and later WooCommerce/Shopify/Meta Catalog sync).
- Delivery-charge rules by zone (e.g. inside / outside Dhaka).
- **Source of truth for prices & stock** — read relationally by the AI layer, never embedded into the vector store (anti-hallucination).
- Catalog edits trigger re-index of affected product descriptions in Knowledge.

## Status
Skeleton only. Models, CSV import, and delivery rules land in E6.
