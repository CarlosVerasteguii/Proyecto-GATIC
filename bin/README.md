# GATIC Workspace Wrappers

Small local Windows wrappers for operator/Codex work in `C:\dev\Proyecto-GATIC`.

Use these from PowerShell or `cmd.exe`:

```powershell
.\bin\gatic-doctor.cmd
.\bin\gatic-status.cmd
.\bin\gatic-artisan.cmd --version
.\bin\gatic-artisan.cmd test
```

The `.cmd` files call the matching `.ps1` scripts with `-ExecutionPolicy Bypass`,
so they are the most reliable entry points for Codex CLI sessions.

## Scripts

- `gatic-doctor.ps1`: validates workspace shape, required files, and tool availability.
- `gatic-status.ps1`: prints current directory, git repo, branch, dirty state, and repo root.
- `gatic-artisan.ps1`: runs Laravel `artisan` inside Docker Compose (`gatic/compose.yaml`).

## Notes

- GATIC's Laravel app lives in `gatic/` (not repo root).
- Canonical app commands are documented in `AGENTS.md` (repo root).
- For multi-session work, use exec plans under `GaticLastRound/exec-plans/`.
