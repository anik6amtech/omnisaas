# Orders

> Bounded context: lead/order capture, buyer payment links, stock decrement.

- **Plane:** tenant
- **Epic:** E7 (Phase 1)

## Responsibilities
- Order/lead capture via AI structured slot-fill (`Prism::structured()`: product → variant → qty → name → phone → address → zone).
- Lightweight order CRM: status pipeline (new → confirmed → shipped → delivered → cancelled/returned).
- **Buyer** payment links (SSLCommerz for BD; later Stripe/PayPal) + IPN callbacks — distinct from tenant *subscription* billing (see Billing).
- Order creation + stock decrement in one transaction with `lockForUpdate()` to prevent overselling.

## Status
Skeleton only. Models, structured capture, and buyer payments land in E7.
