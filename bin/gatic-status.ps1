$ErrorActionPreference = 'Continue'
Set-StrictMode -Version Latest

$CurrentDir = (Get-Location).ProviderPath

Write-Output "cwd: $CurrentDir"

if (-not (Get-Command git.exe -ErrorAction SilentlyContinue)) {
    Write-Error 'git: unavailable'
    exit 1
}

$RepoRoot = (& git.exe rev-parse --show-toplevel 2>$null)
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($RepoRoot)) {
    Write-Output 'git: no repo'
    exit 0
}

$RepoRoot = [IO.Path]::GetFullPath($RepoRoot.Trim())
$RepoName = Split-Path -Leaf $RepoRoot
$Branch = (& git.exe symbolic-ref --quiet --short HEAD 2>$null)
if ($LASTEXITCODE -ne 0 -or [string]::IsNullOrWhiteSpace($Branch)) {
    $Branch = (& git.exe rev-parse --short HEAD).Trim()
} else {
    $Branch = $Branch.Trim()
}

$StatusOutput = @(& git.exe status --porcelain=1)
$Tracked = $false
$Untracked = $false

foreach ($line in $StatusOutput) {
    if ([string]::IsNullOrWhiteSpace($line)) {
        continue
    }

    if ($line.StartsWith('??')) {
        $Untracked = $true
    } else {
        $Tracked = $true
    }
}

if (-not $Tracked -and -not $Untracked) {
    $Changes = 'clean'
} elseif ($Tracked -and $Untracked) {
    $Changes = 'tracked+untracked'
} elseif ($Tracked) {
    $Changes = 'tracked'
} else {
    $Changes = 'untracked'
}

Write-Output "repo: $RepoName"
Write-Output "branch: $Branch"
Write-Output "changes: $Changes"
Write-Output "root: $RepoRoot"

