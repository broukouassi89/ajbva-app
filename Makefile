# AJBVA — Makefile
# Commandes de développement pratiques

.PHONY: help install dev db-reset fixtures cache-clear test lint

# Affiche l'aide
help:
	@echo ""
	@echo "  ╔══════════════════════════════════════════╗"
	@echo "  ║    AJBVA — Commandes disponibles         ║"
	@echo "  ╚══════════════════════════════════════════╝"
	@echo ""
	@echo "  make install      → Installation complète"
	@echo "  make dev          → Lancer le serveur de développement"
	@echo "  make db-reset     → Réinitialiser la base de données"
	@echo "  make fixtures     → Charger les données de test"
	@echo "  make cache-clear  → Vider le cache"
	@echo "  make test         → Lancer les tests PHPUnit"
	@echo "  make lint         → Vérifier le code PHP"
	@echo ""

# Installation complète
install:
	@echo "🚀 Installation de l'application AJBVA..."
	composer install --no-scripts --optimize-autoloader
	cp -n .env .env.local || true
	mkdir -p public/uploads/photos var/cache var/log var/sessions
	php bin/console doctrine:database:create --no-interaction --if-not-exists
	php bin/console doctrine:migrations:migrate --no-interaction
	php bin/console cache:clear
	@echo "✅ Installation terminée !"
	@echo "📌 Lancez : make dev"

# Serveur de développement
dev:
	@echo "🌐 Démarrage du serveur sur http://localhost:8000"
	symfony serve --no-tls

# Réinitialiser la BDD
db-reset:
	@echo "⚠️  Réinitialisation de la base de données..."
	php bin/console doctrine:database:drop --force --no-interaction --if-exists
	php bin/console doctrine:database:create --no-interaction
	php bin/console doctrine:migrations:migrate --no-interaction
	@echo "✅ Base de données réinitialisée."

# Charger les fixtures
fixtures: db-reset
	@echo "🌱 Chargement des données de démonstration..."
	php bin/console doctrine:fixtures:load --no-interaction
	@echo "✅ Données chargées !"
	@echo ""
	@echo "  Comptes de test :"
	@echo "  admin@ajbva.ci          / Admin@2024!"
	@echo "  bureau@ajbva.ci         / Bureau@2024!"
	@echo "  vp.social@ajbva.ci      / VPSocial@2024!"
	@echo "  jean.kouassi@email.com  / Membre@2024!"

# Vider le cache
cache-clear:
	php bin/console cache:clear
	php bin/console cache:warmup

# Tests
test:
	php bin/phpunit

# Lint
lint:
	php bin/console lint:twig templates/
	php bin/console lint:yaml config/
	php bin/console doctrine:schema:validate

# Migration
migrate:
	php bin/console doctrine:migrations:migrate --no-interaction

# Nouvelle migration
migration-diff:
	php bin/console doctrine:migrations:diff
