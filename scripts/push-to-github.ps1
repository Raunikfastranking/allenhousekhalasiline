# Push allenhousekhalasiline.com directly to its standalone GitHub repo.
$ErrorActionPreference = 'Stop'
$site = Split-Path $PSScriptRoot -Parent
$remote = 'https://github.com/fastranking856-max/allenhousekhalasiline.git'
$pushAll = Join-Path (Split-Path $site -Parent) 'scripts\push-all-schools-to-github.ps1'

& $pushAll -Only khalasiline
