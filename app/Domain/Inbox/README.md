# Inbox

> Bounded context: conversations, threading, 24-hour window tracking, assignment, status.

- **Plane:** tenant (custom Livewire + Reverb)
- **Epic:** E5

## Responsibilities
- Conversation state: threading, status (ai-handled / needs-human / resolved), assignment to agents, internal notes/tags.
- **Per-channel 24-hour window tracking** (`window_expires_at` in Postgres + Redis) — the single most important inbox signal.
- AI ⇄ human toggle and one-tap takeover; escalation/handoff.
- Realtime: Livewire components (`ConversationList`, `ConversationThread`, `Composer`) subscribed to Echo private channels (`workspace.{id}.inbox`, `conversation.{id}`), broadcast over Reverb.

## Status
Skeleton only. Models, window service, Livewire UI, and realtime events land in E5 (the `WorkspacePinged` demo event shows the broadcast path).
