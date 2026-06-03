# AI

> Bounded context: intent classification, RAG orchestration, guardrails, tool/function execution.

- **Plane:** tenant (runs in the `ai` queue)
- **Epic:** E4 (OpenRouter via Prism)

## Responsibilities
- **Intent classification** (cheap/fast model) over the f-commerce taxonomy.
- **Hybrid grounding retrieval**: structured facts (price/stock/delivery) from **relational** catalog queries — *never* vectors; unstructured context (policy/FAQ) from **pgvector** similarity search, tenant-scoped. This split is the core anti-hallucination guarantee.
- **Compose** with Prism, exposing allow-listed tools: `create_order`, `generate_payment_link`, `escalate_to_human`, `schedule_followup`.
- **Guardrails**: confidence threshold + handoff, allowed-action whitelist, policy validation, anger/refund/legal detection → human. Never invent prices/stock/policy; never use the Human Agent tag.
- Provider routing/failover (OpenRouter `allow_fallbacks` + Prism fallback); per-workspace token metering; response caching; intent short-circuit to canned answers.
- **Eval harness** (Bangla/Banglish/English fixtures) — full-auto mode is gated on passing the quality bar.

## Status
Skeleton only. Prism is wired via config (`config/prism.php`, OpenRouter); orchestration, retrieval, tools, and guardrails land in E4.
