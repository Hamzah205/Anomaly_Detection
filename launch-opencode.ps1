param(
    [switch]$NoEnv
)

$ErrorActionPreference = "Stop"
$projectRoot = $PSScriptRoot
$envFile = Join-Path $projectRoot ".env"

# --- Muat .env ke environment proses (openccode TIDAK membaca .env otomatis) ---
if (-not $NoEnv) {
    if (Test-Path -LiteralPath $envFile) {
        $loaded = 0
        Get-Content -LiteralPath $envFile | ForEach-Object {
            $line = $_.Trim()
            if ($line -and -not $line.StartsWith("#") -and $line -match "=") {
                $name  = ($line.Substring(0, $line.IndexOf("="))).Trim().Trim('"', "'")
                $value = ($line.Substring($line.IndexOf("=") + 1)).Trim().Trim('"', "'")
                if ($name) {
                    [Environment]::SetEnvironmentVariable($name, $value, "Process")
                    $loaded++
                }
            }
        }
        Write-Host "[env] Loaded $loaded variable(s) from $envFile" -ForegroundColor Green
    }
    else {
        Write-Host "[env] $envFile not found. Copy .env.example to .env and fill in API keys." -ForegroundColor Yellow
    }
}

# --- Jalankan opencode dengan argumen yang diteruskan ---
Write-Host "[opencode] Starting..." -ForegroundColor Cyan
& opencode @args
exit $LASTEXITCODE
