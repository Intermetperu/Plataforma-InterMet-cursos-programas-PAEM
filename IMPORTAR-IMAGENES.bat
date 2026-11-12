@echo off
chcp 65001 >nul
title Plataforma InterMet - importar imagenes de los cursos
cd /d "%~dp0"
echo.
echo  ==========================================================
echo     IMPORTAR IMAGENES DE LOS CURSOS
echo  ==========================================================
echo.
echo  Ponga el archivo Imagenes-cursos-InterMet.zip en esta carpeta
echo  (la carpeta "plataforma") y presione una tecla.
echo.
pause
set "PHP=php"
where php >nul 2>nul
if errorlevel 1 (
    for /d %%D in ("C:\laragon\bin\php\php-*") do if exist "%%D\php.exe" set "PHP=%%D\php.exe"
)
"%PHP%" artisan cursos:importar-imagenes Imagenes-cursos-InterMet.zip
echo.
pause
