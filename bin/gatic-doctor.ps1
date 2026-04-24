$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$WorkspaceRoot = (Resolve-Path -LiteralPath (Join-Path $ScriptDir '..')).ProviderPath
$CurrentDir = (Get-Location).ProviderPath
$Failures = 0

function Write-Ok {
    param([string] $Message)
    Write-Output "OK   $Message"
}

function Write-Warn {
    param([string] $Message)
    Write-Output "WARN $Message"
}

function Write-Fail {
    param([string] $Message)
    Write-Output "FAIL $Message"
    $script:Failures++
}

function Test-InsidePath {
    param([string] $Path, [string] $Root)

    $normalizedPath = [IO.Path]::GetFullPath($Path).TrimEnd('\')
    $normalizedRoot = [IO.Path]::GetFullPath($Root).TrimEnd('\')

    return $normalizedPath.Equals($normalizedRoot, [StringComparison]::OrdinalIgnoreCase) -or
        $normalizedPath.StartsWith("$normalizedRoot\", [StringComparison]::OrdinalIgnoreCase)
}

function Check-File {
    param([string] $Label, [string] $Path)

    if (Test-Path -LiteralPath $Path -PathType Leaf) {
        Write-Ok "$Label`: $Path"
    } else {
        Write-Fail "$Label`: missing ($Path)"
    }
}

function Check-Dir {
    param([string] $Label, [string] $Path)

    if (Test-Path -LiteralPath $Path -PathType Container) {
        Write-Ok "$Label`: $Path"
    } else {
        Write-Fail "$Label`: missing ($Path)"
    }
}

function Check-Command {
    param([string] $Label, [string] $Name)

    $command = Get-Command $Name -ErrorAction SilentlyContinue
    if ($command) {
        Write-Ok "$Label`: $($command.Source)"
    } else {
        Write-Fail "$Label`: missing"
    }
}

Write-Output "workspace: $WorkspaceRoot"
Write-Output "cwd: $CurrentDir"

if (Test-InsidePath $CurrentDir $WorkspaceRoot) {
    Write-Ok 'cwd is inside workspace'
} else {
    Write-Fail 'cwd is outside workspace'
}

Check-File 'AGENTS.md' (Join-Path $WorkspaceRoot 'AGENTS.md')
Check-File 'docsBmad/project-context.md' (Join-Path $WorkspaceRoot 'docsBmad\\project-context.md')
Check-File 'project-context.md' (Join-Path $WorkspaceRoot 'project-context.md')
Check-File '_bmad-output/architecture.md' (Join-Path $WorkspaceRoot '_bmad-output\\architecture.md')
Check-File '_bmad-output/prd.md' (Join-Path $WorkspaceRoot '_bmad-output\\prd.md')
Check-File '.github/workflows/ci.yml' (Join-Path $WorkspaceRoot '.github\\workflows\\ci.yml')
Check-File 'GaticLastRound/README.md' (Join-Path $WorkspaceRoot 'GaticLastRound\\README.md')
Check-Dir 'gatic/' (Join-Path $WorkspaceRoot 'gatic')
Check-File 'gatic/compose.yaml' (Join-Path $WorkspaceRoot 'gatic\\compose.yaml')
Check-File 'gatic/artisan' (Join-Path $WorkspaceRoot 'gatic\\artisan')
Check-File 'gatic/docs/agent-enforcement.md' (Join-Path $WorkspaceRoot 'gatic\\docs\\agent-enforcement.md')

Check-Command 'powershell.exe' 'powershell.exe'
Check-Command 'cmd.exe' 'cmd.exe'
Check-Command 'git.exe' 'git.exe'
Check-Command 'docker.exe' 'docker.exe'

$docker = Get-Command docker.exe -ErrorAction SilentlyContinue
if ($docker) {
    & cmd.exe /c "docker compose version >nul 2>nul"
    if ($LASTEXITCODE -eq 0) {
        Write-Ok 'docker compose is available'
    } else {
        Write-Warn 'docker compose is not available'
    }

    & cmd.exe /c "docker ps >nul 2>nul"
    if ($LASTEXITCODE -eq 0) {
        Write-Ok 'docker engine is reachable'
    } else {
        Write-Warn 'docker engine is not reachable (Docker may be stopped)'
    }

    try {
        Push-Location -LiteralPath (Join-Path $WorkspaceRoot 'gatic')
        & cmd.exe /c "docker compose -f compose.yaml config -q >nul 2>nul"
        if ($LASTEXITCODE -eq 0) {
            Write-Ok 'gatic compose.yaml parses'
        } else {
            Write-Warn 'gatic compose.yaml could not be validated via docker compose'
        }
    } catch {
        Write-Warn 'gatic compose.yaml could not be validated via docker compose'
    } finally {
        try { Pop-Location } catch {}
    }
}

if ($Failures -gt 0) {
    $suffix = if ($Failures -eq 1) { '' } else { 's' }
    Write-Output "result: FAIL ($Failures critical issue$suffix)"
    exit 1
}

Write-Output 'result: PASS'
