@echo off
echo ==========================================
echo Iniciando Servidor de Desarrollo TAMEP...
echo ==========================================
echo.
echo Presiona CTRL+C en esta ventana para detener el servidor.
echo.
echo El sistema estara disponible en: http://localhost:8000
echo.
cd public
c:\xampp\php\php.exe -S localhost:8000 index.php
pause
