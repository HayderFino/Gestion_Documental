@echo off
echo Limpiando archivos que deben ser ignorados...
git rm -r --cached .agents/workflows 2>nul
git add .
git commit -m "chore: remover archivos ignorados del repositorio"
git push origin main
echo Proceso de limpieza finalizado.
pause
