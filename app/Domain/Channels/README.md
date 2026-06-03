# Channels

> Bounded context: Meta integration — WhatsApp / Instagram / Messenger drivers, webhook verification, token vault.

- **Plane:** tenant (per-workspace connected channels)
- **Epic:** E2 (contract present; drivers in E2, Instagram in E11)

## Responsibilities
- One driver per channel type behind a single contract, so the rest of the system is channel-agnostic.
- Webhook **HMAC signature verification** (X-Hub-Signature-256) on every payload.
- Encrypted **token vault** (`channels.access_token` → `encrypted` cast) + long-lived token refresh.
- `ChannelManager` (Laravel Manager pattern) resolving a driver by channel type.
- Comment-to-DM as a distinct inbound event type (E11).

## Contracts
- `Contracts\ChannelDriver` — `verifyWebhook` · `parseInbound` · `send` · `sendTemplate` · `windowRules`.
- `Data\InboundMessage`, `Data\WindowPolicy` — canonical DTOs (stubs).

## Status
Contract + DTO stubs only. `WhatsAppCloudDriver`, `MessengerDriver`, transport (HTTP client + per-account circuit breaker), and webhook middleware land in E2.
