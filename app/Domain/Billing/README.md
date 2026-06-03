# Billing

> Bounded context: plans, subscriptions, usage metering — the in-app subscription engine.

- **Plane:** both (operator defines plans; tenant subscribes)
- **Epic:** E8 (Phase 1)

## Responsibilities
- **In-app billing engine** (SSLCommerz is the payment *executor* only — no native subscription manager; Cashier doesn't apply).
- Tokenized auto-rebill (primary) + manual renewal (fallback); **IPN is the source of truth** (validated by `val_id`, idempotent on `tran_id`) — browser return URLs are never trusted alone.
- Subscription lifecycle state machine: trialing → active → past_due → grace → suspended / cancelled; dunning + reminders.
- **Entitlements**: plan limits (channels, AI replies/mo, seats, products) enforced via `usage_counters`; feature flags via Pennant. DB-defined, panel-editable (E9) — zero deploy.
- Data: `plans`, `subscriptions`, `invoices`, `payment_transactions`, `payment_tokens` (token ref only → minimal PCI scope), `usage_counters`.

## Status
Skeleton only. The `CommentToDm` Pennant flag seeds the entitlements idea; the engine, SSLCommerz integration, and dunning land in E8.
