param(
    [switch] $IncludeVendor,
    [switch] $IncludeDocs
)

$ErrorActionPreference = 'Stop'

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$releaseRoot = Join-Path $projectRoot "releases\$timestamp"
$packageRoot = Join-Path $releaseRoot 'package'
$zipPath = Join-Path $projectRoot "releases\grihasthikart-$timestamp.zip"

$requiredDirectories = @(
    'app',
    'bootstrap',
    'config',
    'database',
    'public',
    'resources',
    'routes',
    'storage\app\public'
)

$requiredFiles = @(
    'artisan',
    'composer.json',
    'composer.lock',
    '.env.production.example'
)

if ($IncludeVendor) {
    $requiredDirectories += 'vendor'
}

if ($IncludeDocs) {
    $requiredDirectories += 'docs\08_Deployment'
}

New-Item -ItemType Directory -Path $packageRoot -Force | Out-Null

foreach ($directory in $requiredDirectories) {
    $source = Join-Path $projectRoot $directory

    if (Test-Path $source) {
        $destination = Join-Path $packageRoot $directory
        New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force | Out-Null
        Copy-Item -Path $source -Destination $destination -Recurse -Force
    }
    else {
        Write-Warning "Skipped missing directory: $directory"
    }
}

foreach ($file in $requiredFiles) {
    $source = Join-Path $projectRoot $file

    if (Test-Path $source) {
        $destination = Join-Path $packageRoot $file
        New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force | Out-Null
        Copy-Item -Path $source -Destination $destination -Force
    }
    else {
        Write-Warning "Skipped missing file: $file"
    }
}

$pathsToRemove = @(
    '.env',
    '.env.backup',
    '.env.production',
    '.git',
    'node_modules',
    'tests',
    'storage\logs',
    'storage\framework\cache',
    'storage\framework\sessions',
    'storage\framework\views',
    'public\hot',
    'public\storage'
)

foreach ($path in $pathsToRemove) {
    $target = Join-Path $packageRoot $path

    if (Test-Path $target) {
        Remove-Item -Path $target -Recurse -Force
    }
}

if (-not (Test-Path (Join-Path $packageRoot 'public\build'))) {
    Write-Warning 'public/build was not found in the package. Run npm run build before packaging.'
}

if ((Test-Path (Join-Path $packageRoot '.env')) -or (Test-Path (Join-Path $packageRoot '.env.production'))) {
    throw 'Unsafe package detected: real environment file was copied.'
}

New-Item -ItemType Directory -Path (Split-Path $zipPath -Parent) -Force | Out-Null

if (Test-Path $zipPath) {
    Remove-Item -Path $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$packageRootPath = (Resolve-Path $packageRoot).Path.TrimEnd('\', '/')
$zip = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    Get-ChildItem -Path $packageRootPath -Recurse -File | ForEach-Object {
        $relativePath = $_.FullName.Substring($packageRootPath.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $zip,
            $_.FullName,
            $relativePath,
            [System.IO.Compression.CompressionLevel]::Optimal
        ) | Out-Null
    }
}
finally {
    $zip.Dispose()
}

Write-Host "Release package created: $zipPath"
Write-Host "Package staging folder: $packageRoot"
Write-Host ''
Write-Host 'Next steps:'
Write-Host '1. Review the ZIP contents before upload.'
Write-Host '2. Create the real .env manually on Hostinger.'
Write-Host '3. Upload with Hostinger File Manager or SSH.'
Write-Host '4. Configure public_html mapping and storage link.'
Write-Host '5. Run the final post-live checklist.'
