@echo off
SETLOCAL EnableDelayedExpansion

echo ===================================================
echo   AJBVA - Lancement de l'application (Windows)
echo ===================================================

:: Vérification de PHP
php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERREUR] PHP n'est pas installe ou n'est pas dans le PATH.
    pause
    exit /b 1
)

:: Vérification de Composer
composer -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERREUR] Composer n'est pas installe ou n'est pas dans le PATH.
    pause
    exit /b 1
)

:: Installation des dépendances si le dossier vendor n'existe pas
if not exist "vendor" (
    echo [INFO] Installation des dependances...
    composer install --no-interaction
)

:: Configuration de l'environnement
if not exist ".env.local" (
    echo [INFO] Creation du fichier .env.local...
    copy .env .env.local
)

:: Création de la base de données SQLite
if not exist "var/data.db" (
    echo [INFO] Initialisation de la base de données...
    php bin/console doctrine:database:create --if-not-exists
    php bin/console doctrine:migrations:migrate --no-interaction
    php bin/console doctrine:fixtures:load --no-interaction
)

:: Nettoyage du cache
echo [INFO] Nettoyage du cache...
php bin/console cache:clear

echo ===================================================
echo   Application prete !
echo   Acces : http://localhost:8000
echo   Appuyez sur Ctrl+C pour arreter le serveur.
echo ===================================================

php -S localhost:8000 -t public
pause
