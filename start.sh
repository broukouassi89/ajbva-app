#!/bin/bash

echo "==================================================="
echo "  AJBVA - Lancement de l'application (Unix/Mac)"
echo "==================================================="

# Vérification de PHP
if ! command -v php &> /dev/null; then
    echo "[ERREUR] PHP n'est pas installé."
    exit 1
fi

# Vérification de Composer
if ! command -v composer &> /dev/null; then
    echo "[ERREUR] Composer n'est pas installé."
    exit 1
fi

# Installation des dépendances
if [ ! -d "vendor" ]; then
    echo "[INFO] Installation des dépendances..."
    composer install --no-interaction
fi

# Configuration de l'environnement
if [ ! -f ".env.local" ]; then
    echo "[INFO] Création du fichier .env.local..."
    cp .env .env.local
fi

# Création de la base de données SQLite
if [ ! -f "var/data.db" ]; then
    echo "[INFO] Initialisation de la base de données..."
    php bin/console doctrine:database:create --if-not-exists
    php bin/console doctrine:migrations:migrate --no-interaction
    php bin/console doctrine:fixtures:load --no-interaction
fi

# Nettoyage du cache
echo "[INFO] Nettoyage du cache..."
php bin/console cache:clear

echo "==================================================="
echo "  Application prête !"
echo "  Accès : http://localhost:8000"
echo "  Appuyez sur Ctrl+C pour arrêter le serveur."
echo "==================================================="

php -S localhost:8000 -t public
