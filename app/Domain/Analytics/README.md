# Analytics

> Bounded context: rollups, resolution rate, conversion, cost.

- **Plane:** both (tenant dashboards + operator platform analytics)
- **Epic:** continuous

## Responsibilities
- Tenant metrics: volume, median response time, **AI resolution rate**, handoff rate (per channel); inquiry→order conversion and AI-attributed revenue; top questions and the **list of questions the AI couldn't answer** (feeds the knowledge base).
- Operator/platform metrics: MRR/ARR, churn, signups, active/trial/suspended tenants, AI usage & OpenRouter cost across all tenants.
- Read from **scheduler rollups** of `usage_events` to keep dashboards fast (optionally a Postgres read replica).

## Status
Skeleton only. Rollup jobs and dashboards are built incrementally as upstream data lands.
