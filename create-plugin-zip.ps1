# WordPress Plugin ZIP Creation Script
# Creates UNIX/Linux-compatible ZIP in plugin/ subfolder

$pluginFolderName = Split-Path -Leaf (Get-Location)
$sourceDir = Get-Location
$pluginSubfolder = Join-Path $sourceDir "plugin"

# Extract version from main plugin file
$mainPluginFile = Join-Path $sourceDir "$pluginFolderName.php"
$pluginVersion = ""
if (Test-Path $mainPluginFile) {
    $content = Get-Content $mainPluginFile -Raw
    if ($content -match 'Version:\s*([^\r\n]+)') {
        $pluginVersion = $matches[1].Trim()
    }
}

# Create ZIP filename with version
if ($pluginVersion) {
    $zipFileName = "$pluginFolderName-$pluginVersion.zip"
} else {
    $zipFileName = "$pluginFolderName.zip"
}
$zipPath = Join-Path $pluginSubfolder $zipFileName

# Create plugin subfolder and remove old ZIPs
New-Item -ItemType Directory -Path $pluginSubfolder -Force | Out-Null
Get-ChildItem -Path $pluginSubfolder -Filter "*.zip" | Remove-Item -Force -ErrorAction SilentlyContinue

# Load compression assembly
Add-Type -AssemblyName System.IO.Compression

# Get files to include
$files = Get-ChildItem -Path $sourceDir -Recurse -File | Where-Object {
    $rel = $_.FullName.Substring($sourceDir.Path.Length + 1)
    -not (
        ($rel -match '(^|\\)\.' -and $rel -notmatch '\.(php|js|css|json)$') -or
        ($_.Extension -in '.md','.ps1','.zip') -or
        ($rel -match '(^|\\)(node_modules|src-svelte|plugin|\.git)\\') -or
        ($_.Name -match '^(package\.json|package-lock\.json|vite\.config\.js|svelte\.config\.js|tsconfig\.json)$')
    )
}

# Create ZIP with forward slashes
$fileStream = [System.IO.File]::Create($zipPath)
$zip = New-Object System.IO.Compression.ZipArchive($fileStream, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    foreach ($file in $files) {
        $rel = $file.FullName.Substring($sourceDir.Path.Length + 1)
        $unixPath = $rel.Replace('\', '/')
        $entryPath = "$pluginFolderName/$unixPath"

        $entry = $zip.CreateEntry($entryPath, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        $fileStream2 = [System.IO.File]::OpenRead($file.FullName)
        $fileStream2.CopyTo($entryStream)
        $fileStream2.Close()
        $entryStream.Close()
    }
} finally {
    $zip.Dispose()
    $fileStream.Close()
}

# Output result
$size = [math]::Round((Get-Item $zipPath).Length / 1KB, 0)
Write-Host "Created: plugin/$zipFileName ($size KB, $($files.Count) files)"
