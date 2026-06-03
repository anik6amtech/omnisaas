# Messaging

> Bounded context: canonical inbound/outbound messages, idempotency, the async pipeline, the outbound dispatcher.

- **Plane:** tenant
- **Epic:** E3 (DTO stubs present)

## Responsibilities
- The canonical `Message` model (ULID PK, `external_id` unique → idempotency; monthly range partitioning at scale).
- The async **three-job pipeline**: `IngestInboundMessage` (webhooks) → `GenerateAiReply` (ai) → `SendOutboundMessage` (dispatch). Webhooks are never processed inline.
- The **outbound dispatcher**: re-checks window legality before every send, applies per-account rate limiting (token bucket), and **hard-blocks the Human Agent tag for AI sends** (Meta-prohibited).
- Job middleware: `WithoutOverlapping` (per conversation), `RateLimited` (per channel account), `ThrottlesExceptions`.

## Contracts
- `Data\OutboundMessage`, `Data\TemplateMessage`, `Data\SendResult` — DTO stubs consumed by the dispatcher / `ChannelDriver`.

## Status
DTO stubs only. Models, jobs, queues, and the dispatcher land in E3.
