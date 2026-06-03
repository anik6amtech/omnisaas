# Tenancy

> Bounded context: workspaces, members, roles, invitations — the multi-tenant root.

- **Plane:** both (every tenant-scoped table FKs to a workspace)
- **Epic:** E1 (foundation primitives present)

## Responsibilities
- Workspaces (tenants), membership + tenant roles (owner / admin / agent), invitations.
- Tenant isolation: `workspace_id` scoping via the global scope; optional Postgres RLS as defense-in-depth.
- Resolving and binding the active workspace per request/job (Octane-safe).

## Implemented in this phase
- `Concerns\BelongsToWorkspace` — trait: global scope + auto-fill `workspace_id` + `workspace()` relation.
- `Scopes\WorkspaceScope` — the global scope (default-isolated reads).
- `Context\CurrentWorkspace` — container-scoped active-workspace holder (flushed by Octane per request).
- Models `Workspace`, `User` (web guard), `AdminUser` (admin guard); `workspace_user` pivot.

## Next
Onboarding wizard, invitations, RLS policies (E1).
