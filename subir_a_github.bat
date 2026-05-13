@echo off
git init
git remote add origin https://github.com/HayderFino/Gestion_Documental.git
git add .
git commit -m "Initial commit - SGD CAS logic and documentation"
git branch -M main
git push -u origin main
echo Proceso finalizado.
pause
