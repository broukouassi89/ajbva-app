@echo off
SETLOCAL EnableDelayedExpansion

echo ===================================================
echo   AJBVA - REMISE A ZERO DE LA BASE DE DONNEES
echo ===================================================
echo.
echo ATTENTION : Cette operation va supprimer TOUTES les donnees actuelles.
echo.
set /p confirm="Voulez-vous continuer ? (O/N) : "

if /i "%confirm%" neq "O" (
    echo Operation annulee.
    pause
    exit /b 0
)

echo [INFO] Suppression de la base de donnees actuelle...
if exist "var\data.db" del /f /q "var\data.db"

echo [INFO] Creation de la base de donnees...
php bin/console doctrine:database:create --if-not-exists

echo [INFO] Application du schema (migrations)...
php bin/console doctrine:migrations:migrate --no-interaction

echo [INFO] Nettoyage du cache...
php bin/console cache:clear

echo.
echo ===================================================
echo   Base de donnees remise a neuf !
echo   Note : Aucun utilisateur n'existe pour le moment.
echo   Utilisez 'php bin/console doctrine:fixtures:load' 
echo   si vous voulez remettre les comptes de demo.
echo ===================================================
pause
