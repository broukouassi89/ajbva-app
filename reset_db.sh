#!/bin/bash

echo "==================================================="
echo "  AJBVA - REMISE A ZERO DE LA BASE DE DONNEES"
echo "==================================================="
echo ""
echo "ATTENTION : Cette opération va supprimer TOUTES les données actuelles."
echo ""
read -p "Voulez-vous continuer ? (O/N) : " confirm

if [[ $confirm != "O" && $confirm != "o" ]]; then
    echo "Opération annulée."
    exit 0
fi

echo "[INFO] Suppression de la base de données actuelle..."
if [ -f "var/data.db" ]; then
    rm -f var/data.db
fi

echo "[INFO] Création de la base de données..."
php bin/console doctrine:database:create --if-not-exists

echo "[INFO] Application du schéma (migrations)..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "[INFO] Nettoyage du cache..."
php bin/console cache:clear

echo ""
echo "==================================================="
echo "  Base de données remise à neuf !"
echo "==================================================="
