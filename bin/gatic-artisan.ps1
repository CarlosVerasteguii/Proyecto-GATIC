param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $RemainingArgs
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$WorkspaceRoot = (Resolve-Path -LiteralPath (Join-Path $ScriptDir '..')).ProviderPath

function Resolve-LaravelRepoRoot {
    param([string] $Candidate)

    if (-not (Test-Path -LiteralPath $Candidate -PathType Container)) {
        return $null
    }

    $current = (Resolve-Path -LiteralPath $Candidate).ProviderPath

    while ($true) {
        if (Test-Path -LiteralPath (Join-Path $current 'artisan') -PathType Leaf) {
            return $current
        }

        $parent = Split-Path -Parent $current
        if ([string]::IsNullOrEmpty($parent) -or [string]::Equals($parent, $current, [StringComparison]::OrdinalIgnoreCase)) {
            break
        }

        $current = $parent
    }

    return $null
}

function Fail-Artisan {
    param([string] $Message)

    [Console]::Error.WriteLine("gatic-artisan: $Message")
    exit 1
}

if (-not (Get-Command docker.exe -ErrorAction SilentlyContinue)) {
    Fail-Artisan 'docker.exe not found'
}

& cmd.exe /c "docker ps >nul 2>nul"
if ($LASTEXITCODE -ne 0) {
    Fail-Artisan 'docker engine is not reachable (start Docker Desktop)'
}

$ExplicitTarget = $false
$TargetDir = (Join-Path $WorkspaceRoot 'gatic')
$ArtisanArgs = @()

for ($i = 0; $i -lt $RemainingArgs.Count; $i++) {
    $arg = $RemainingArgs[$i]

    if ($arg -eq '--repo') {
        if ($ExplicitTarget) {
            Fail-Artisan '--repo may only be provided once'
        }

        $nextIndex = $i + 1
        if ($nextIndex -ge $RemainingArgs.Count -or [string]::IsNullOrWhiteSpace($RemainingArgs[$nextIndex]) -or $RemainingArgs[$nextIndex].StartsWith('--')) {
            Fail-Artisan 'missing path for --repo'
        }

        $TargetDir = $RemainingArgs[$nextIndex]
        $ExplicitTarget = $true
        $i++
        continue
    }

    if ($arg.StartsWith('--repo=')) {
        if ($ExplicitTarget) {
            Fail-Artisan '--repo may only be provided once'
        }

        $TargetDir = $arg.Substring('--repo='.Length)
        if ([string]::IsNullOrWhiteSpace($TargetDir)) {
            Fail-Artisan 'missing path for --repo'
        }

        $ExplicitTarget = $true
        continue
    }

    $ArtisanArgs += $arg
}

if ($ExplicitTarget) {
    if (-not (Test-Path -LiteralPath $TargetDir)) {
        Fail-Artisan "explicit repo path does not exist: $TargetDir"
    }

    if (-not (Test-Path -LiteralPath $TargetDir -PathType Container)) {
        Fail-Artisan "explicit repo path is not a directory: $TargetDir"
    }
}

$RepoDir = Resolve-LaravelRepoRoot $TargetDir
if (-not $RepoDir) {
    if ($ExplicitTarget) {
        Fail-Artisan "explicit repo path is not inside a Laravel repo: $TargetDir"
    }

    Fail-Artisan "default Laravel repo not found: $TargetDir"
}

Push-Location -LiteralPath $RepoDir
try {
    & docker.exe compose -f compose.yaml exec -T laravel.test php artisan @ArtisanArgs
    exit $LASTEXITCODE
} finally {
    Pop-Location
}
