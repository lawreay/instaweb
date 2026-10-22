# InstaWeb OpenVSCode Server: Local Proof of Concept

This experiment tests the editor UX only. It is **not connected to InstaWeb, InfinityFree, or any customer files**.

## Prerequisites

- Docker Desktop installed and running on Windows, macOS, or Linux.
- Git is optional if you already have the repository checkout.

InfinityFree does not support Docker or custom server processes, so this container runs on a developer machine only.

## Start

From the repository root:

```bash
docker compose -f experiments/openvscode-poc/compose.yaml up -d
```

Open http://127.0.0.1:3000 in your browser.

The container mounts only this experiment's `workspace/` directory. The host port is bound to loopback and should not be changed to `0.0.0.0`. The image's default unauthenticated mode is acceptable only for this loopback-only disposable test. Do not forward the port, use a public tunnel, or deploy this configuration.

## Test

1. Confirm the Explorer shows the sample `index.html`.
2. Edit the heading and save.
3. Confirm the change persists after refreshing the browser.
4. Open the built-in terminal and inspect the current directory.
5. Confirm the workspace contains only this experiment's files.
6. Compare load speed and usability with InstaWeb's current editor.

## Stop

```bash
docker compose -f experiments/openvscode-poc/compose.yaml down
```

The sample workspace files remain on disk.

## Security and scope

- Never mount `storage/`, `.env`, the InstaWeb application root, production files, backups, or customer directories.
- This setup deliberately has no connection to production and no customer authentication.
- OpenVSCode Server enables terminal commands, so a production integration requires isolated per-session workspaces, a secure authenticated gateway, resource limits, and explicit authorization.
- This proof of concept does not establish that OpenVSCode Server is production-ready for multi-tenant hosting.
- The `latest` image tag is used only to make the disposable first test easy. Before any persistent deployment, pin a tested release or digest and define an update policy.
