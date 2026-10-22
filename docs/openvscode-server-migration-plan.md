# InstaWeb OpenVSCode Server Migration Plan

**Status:** Discovery complete; proof of concept not yet deployed  
**Branch:** `feature/openvscode-server-poc`  
**Target:** Replace the custom Monaco-based editor only after the proof of concept passes the acceptance checks below.

## Decision

Evaluate **OpenVSCode Server** as the editor runtime rather than expanding the custom editor. Keep the existing editor available as a fallback until the replacement is proven. Do not expose a shared OpenVSCode instance directly to customers.

Official reference: https://github.com/gitpod-io/openvscode-server

## Current InstaWeb architecture (verified from source)

- PHP application, PHP >= 8.0, MySQL, custom MVC routing.
- Website ownership is checked in `EditorController::requireWebsite()` using the authenticated user and `Website::findForUser()`.
- Draft website files live at `storage/projects/{userId}/{slug}`.
- Published output lives at `public/sites/{projectId}/{slug}`.
- The current editor reads and writes draft files through `ProjectStorageService`.
- Existing routes include:
  - `GET /dashboard/websites/editor?id={websiteId}`
  - `GET /dashboard/websites/editor/file?id={websiteId}&path={relativePath}`
  - `POST /dashboard/websites/editor/save`
  - `GET /dashboard/websites/preview?id={websiteId}`
- WordPress projects are stored in the same draft-project tree and contain executable PHP. Therefore, providing a terminal or full IDE is a major increase in capability compared with the current allow-listed text editor.

## Non-negotiable security boundaries

1. **One isolated workspace per active editor session.** Never mount the whole `storage/projects`, `storage`, application root, `.env`, or another customer's project into a customer's editor.
2. **No direct public editor port.** Put the service behind HTTPS and an authenticated gateway/reverse proxy.
3. **Short-lived, scoped authorization.** The PHP app must verify the signed-in user owns the requested website before issuing a short-lived editor session. Do not put a reusable shared token in client-side JavaScript or a query string.
4. **No shared host shell.** Terminal commands can execute arbitrary code. A workspace must run with an unprivileged identity and isolation appropriate to hostile customer code; no host Docker socket, privileged container, host credentials, or unrestricted network access.
5. **Resource limits.** Apply CPU, memory, process, storage, and session time limits; protect the editor gateway against abuse.
6. **Draft-only writes.** The editor mounts draft files only. Publishing remains an explicit InstaWeb operation through the existing publishing workflow.
7. **Audit and revoke.** Record who opened a workspace and when; invalidate access on logout, session expiry, ownership changes, or website deletion.
8. **Preserve existing safeguards.** Keep path validation, website ownership checks, plan storage limits, and publish validation. Do not bypass the app's authorization by trusting an editor URL.

A connection token by itself is not an adequate multi-tenant security design. OpenVSCode Server documentation notes that its instances can share a filesystem; InstaWeb must provide workspace isolation externally.

## Phased implementation

### Phase 0 — Architecture and hosting feasibility (current)
- [x] Inspect current editor controller, routes, file storage, and runtime requirements.
- [x] Confirm draft and published storage roots.
- [x] Identify terminal/code-execution risk for WordPress and customer code.
- [ ] Confirm whether the intended deployment host supports Docker or another supported isolated runtime.
- [ ] Confirm available CPU/RAM/disk and whether a separate editor host is required.

**Exit criteria:** deployment target and isolation strategy are documented. No production customer traffic.

### Phase 1 — Local proof of concept
- Run OpenVSCode Server locally in Docker against a disposable test project.
- Confirm editor startup, Explorer, tabs, search, keyboard shortcuts, save behavior, and PHP/HTML/CSS/JS editing.
- Confirm the container sees only the disposable workspace and runs as an unprivileged user.
- Document actual image, version, resource use, startup procedure, and teardown.
- Do not connect production customer directories yet.

**Exit criteria:** repeatable setup and teardown; no host project or secrets accessible from the workspace.

### Phase 2 — InstaWeb integration contract
- Add a server-side editor-session service only after the runtime is chosen.
- Authenticate and authorize every session from the InstaWeb PHP application.
- Bind each session to one website ID, user ID, draft path, and expiry.
- Proxy the editor over HTTPS without exposing the backend port.
- Ensure cookie, iframe, WebSocket, CSP, CSRF, and reverse-proxy behavior is tested.
- Keep all website writes confined to the selected draft project.

**Exit criteria:** tests prove an owner can open their project and cannot open another user's project by changing IDs, paths, URLs, or tokens.

### Phase 3 — Workspace lifecycle and resource controls
- Create workspace on demand, with per-user/site isolation.
- Apply CPU, RAM, process, disk, and idle-time limits.
- Stop and clean up idle or expired workspaces safely.
- Preserve customer files across workspace restarts; workspace containers themselves must be disposable.
- Define dependency/network access, extension policy, logging, backup, and recovery behavior.

**Exit criteria:** lifecycle tests and security review pass; no cross-tenant access or persistent secrets in containers.

### Phase 4 — UI integration
- Add a full-screen **Open Code Editor** action from Manage My Sites.
- Preserve clear navigation back to the InstaWeb dashboard and website preview.
- Display connection/loading/error states and a safe fallback link to the current editor.
- Do not claim GitHub-style terminal support until the actual server runtime supports it.

**Exit criteria:** keyboard/mouse, responsive layout, browser refresh, session expiry, and reconnect behavior pass.

### Phase 5 — Controlled rollout
- Enable for a test account and disposable static website first.
- Then test a disposable WordPress project without production database credentials.
- Validate save, preview, publish, rollback, quotas, and access revocation.
- Compare load time and resource use against the existing editor.
- Roll out gradually only after all tests pass; keep the old editor as a rollback path.

**Exit criteria:** no critical security findings, measurable startup performance, verified save/publish flow, and a tested rollback.

## Proof-of-concept acceptance checklist

- [ ] OpenVSCode Server starts from a pinned, documented image/version.
- [ ] Browser access requires authenticated gateway access.
- [ ] No public backend port is exposed.
- [ ] Only the disposable test site's files are visible.
- [ ] No `.env`, application source, sibling projects, backups, or published-site roots are visible.
- [ ] The workspace is non-root and has no privileged mode or Docker socket.
- [ ] Editing and saving a file persists in the mounted test workspace.
- [ ] Restarting the container preserves project files but not workspace-only state.
- [ ] Stop/revoke behavior removes access.
- [ ] CPU/RAM/process/disk limits are enforced.
- [ ] PHP app can reject a user who attempts to open another user's website.
- [ ] A PHP/WordPress workspace cannot access production secrets or another site's database credentials.

## Current blockers / decisions required from evidence

- The Git repository does not establish what the production hosting plan supports. Do not assume Docker is available on shared hosting.
- The current project storage contains executable WordPress code; running a terminal against it is materially riskier than editing static files.
- OpenVSCode Server is a runtime, not a drop-in replacement for the current PHP editor routes. A secure bridge and workspace provisioning layer are required.
- No authenticated browser test or deployment test has been performed yet.

## Working rules

- Make each meaningful change in its own small commit.
- Work on `feature/openvscode-server-poc`; do not commit experimental runtime integration directly to `main`.
- Do not modify publishing, billing, authentication, or storage semantics as collateral work.
- Do not delete the current editor until the replacement passes acceptance.
- Report what is verified separately from what is planned or inferred.
