@echo off
setlocal EnableExtensions DisableDelayedExpansion
set "ROOT=%~dp0"
set "WEBAPP=%ROOT%webapp"
set "MLDIR=%ROOT%ml-service"
set "PYTHON=%MLDIR%\venv\Scripts\python.exe"
if not defined SIPKPA_LARAVEL_PORT set "SIPKPA_LARAVEL_PORT=8000"
if not defined SIPKPA_ML_PORT set "SIPKPA_ML_PORT=8001"

if not exist "%WEBAPP%\artisan" goto missing_project
if not exist "%WEBAPP%\vendor\autoload.php" goto missing_vendor
if not exist "%WEBAPP%\.env" goto missing_env
if not exist "%MLDIR%\main.py" goto missing_project
if not exist "%PYTHON%" goto missing_venv
where php >nul 2>nul
if errorlevel 1 goto missing_php
php -r "exit(PHP_VERSION_ID >= 80500 ? 0 : 1);"
if errorlevel 1 goto wrong_php
"%PYTHON%" -c "import fastapi, uvicorn, sklearn, pandas, numpy, joblib, pydantic" >nul 2>nul
if errorlevel 1 goto missing_packages

if exist "%WEBAPP%\public\build\manifest.json" goto check_ports
where npm >nul 2>nul
if errorlevel 1 goto missing_assets
echo [INFO] Membangun aset antarmuka...
pushd "%WEBAPP%"
call npm run build
set "BUILD_RESULT=%ERRORLEVEL%"
popd
if not "%BUILD_RESULT%"=="0" goto missing_assets

:check_ports
powershell -NoProfile -Command "$ports=@($env:SIPKPA_LARAVEL_PORT,$env:SIPKPA_ML_PORT); if ($ports[0] -eq $ports[1]) { Write-Host 'Kedua server harus menggunakan port berbeda.'; exit 1 }; foreach ($value in $ports) { $port=0; if ($value -notmatch '^\d+$' -or -not [int]::TryParse($value,[ref]$port) -or $port -lt 1 -or $port -gt 65535) { Write-Host 'Nomor port tidak valid.'; exit 1 }; $client=New-Object System.Net.Sockets.TcpClient; try { if ($client.ConnectAsync('127.0.0.1',$port).Wait(500) -and $client.Connected) { Write-Host ('Port '+$port+' sedang digunakan. Tutup server lama terlebih dahulu.'); exit 1 } } catch {} finally { $client.Dispose() } }; exit 0"
if errorlevel 1 goto ports_busy
set "ML_SERVICE_URL=http://127.0.0.1:%SIPKPA_ML_PORT%"
powershell -NoProfile -Command "$api=$null; $web=$null; try { $api=Start-Process -FilePath $env:PYTHON -ArgumentList @('-m','uvicorn','main:app','--host','127.0.0.1','--port',$env:SIPKPA_ML_PORT) -WorkingDirectory $env:MLDIR -PassThru; $web=Start-Process -FilePath 'php' -ArgumentList @('artisan','serve','--host=127.0.0.1',('--port='+$env:SIPKPA_LARAVEL_PORT),'--tries=1','--no-reload','--no-interaction') -WorkingDirectory $env:WEBAPP -PassThru; for ($attempt=0; $attempt -lt 80; $attempt++) { if ($api.HasExited -or $web.HasExited) { throw 'Salah satu server berhenti saat startup. Periksa pesan pada jendela server.' }; $ready=0; foreach ($port in @([int]$env:SIPKPA_LARAVEL_PORT,[int]$env:SIPKPA_ML_PORT)) { $client=New-Object System.Net.Sockets.TcpClient; try { if ($client.ConnectAsync('127.0.0.1',$port).Wait(200) -and $client.Connected) { $ready++ } } catch {} finally { $client.Dispose() } }; if ($ready -eq 2) { exit 0 }; Start-Sleep -Milliseconds 250 }; throw 'Server belum siap setelah batas waktu startup.' } catch { Write-Host $_.Exception.Message; if ($web -and -not $web.HasExited) { taskkill /PID $web.Id /T /F }; if ($api -and -not $api.HasExited) { taskkill /PID $api.Id /T /F }; exit 1 }"
if errorlevel 1 goto start_failed
echo.
echo Laravel : http://127.0.0.1:%SIPKPA_LARAVEL_PORT%
echo FastAPI : http://127.0.0.1:%SIPKPA_ML_PORT%
echo Pastikan MySQL Laragon sudah berjalan.
echo Tutup kedua jendela server untuk menghentikan layanan.
exit /b 0

:missing_project
echo [GAGAL] Letakkan start-dev.bat di root bersama folder webapp dan ml-service.
goto failed
:missing_vendor
echo [GAGAL] Dependensi Laravel belum tersedia. Jalankan composer install di webapp.
goto failed
:missing_env
echo [GAGAL] Berkas webapp\.env belum tersedia. Siapkan konfigurasi aplikasi terlebih dahulu.
goto failed
:missing_venv
echo [GAGAL] Virtual environment belum tersedia. Jalankan python -m venv venv di ml-service.
goto failed
:missing_php
echo [GAGAL] PHP tidak ditemukan pada PATH. Buka terminal Laragon dengan PHP 8.5.
goto failed
:wrong_php
echo [GAGAL] Proyek ini menggunakan PHP 8.5. Pilih PHP 8.5 pada Laragon terlebih dahulu.
goto failed
:missing_packages
echo [GAGAL] Instal dependensi: ml-service\venv\Scripts\python.exe -m pip install -r ml-service\requirements.txt
goto failed
:missing_assets
echo [GAGAL] Siapkan aset: jalankan npm install lalu npm run build di webapp.
goto failed
:ports_busy
echo [GAGAL] Periksa ketersediaan dan pengaturan port kedua server.
goto failed
:start_failed
echo [GAGAL] Server tidak berhasil dimulai. Periksa dependensi dan konfigurasi aplikasi.
:failed
exit /b 1
