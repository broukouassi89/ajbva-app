#!/usr/bin/env bash
# ════════════════════════════════════════════════════════════
#  AJBVA — Script d'installation automatique
#  Symfony 7 · PHP 8.2+
# ════════════════════════════════════════════════════════════
set -e

GREEN="\033[0;32m"; BLUE="\033[0;34m"; YELLOW="\033[1;33m"; RED="\033[0;31m"; BOLD="\033[1m"; RESET="\033[0m"

echo -e "\n${BOLD}${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo -e "${BOLD}${BLUE}     AJBVA — Installation Symfony 7            ${RESET}"
echo -e "${BOLD}${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}\n"

# 1. Vérifications
command -v php  >/dev/null || { echo -e "${RED}PHP non trouvé${RESET}"; exit 1; }
command -v composer >/dev/null || { echo -e "${RED}Composer non trouvé${RESET}"; exit 1; }
echo -e "${GREEN}✓ PHP $(php -r 'echo PHP_VERSION;') & Composer détectés${RESET}"

# 2. Installer les dépendances (sans exécuter les scripts Symfony)
echo -e "\n${YELLOW}▶ Installation des dépendances...${RESET}"
composer install --no-interaction --no-scripts --optimize-autoloader
echo -e "${GREEN}✓ Dépendances installées${RESET}"

# 3. Environnement
if [ ! -f .env.local ]; then
    cp .env .env.local
    SECRET=$(php -r "echo bin2hex(random_bytes(16));")
    sed -i.bak "s/changethis_run_php_bin_console_secrets_generate_keys/$SECRET/" .env.local && rm -f .env.local.bak
    echo -e "${GREEN}✓ .env.local créé avec APP_SECRET généré${RESET}"
fi

# 4. Dossiers
mkdir -p public/uploads/photos var/cache var/log var/sessions
echo -e "${GREEN}✓ Dossiers créés${RESET}"

# 5. Base de données
echo -e "\n${YELLOW}▶ Création de la base de données...${RESET}"
php bin/console doctrine:database:create --no-interaction --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
echo -e "${GREEN}✓ Base de données prête${RESET}"

# 6. Fixtures
echo -e "\n${YELLOW}▶ Chargement des données de démonstration...${RESET}"
php bin/console doctrine:fixtures:load --no-interaction
echo -e "${GREEN}✓ Données chargées${RESET}"

# 7. Cache (après que tout est configuré)
echo -e "\n${YELLOW}▶ Chargement du cache...${RESET}"
php bin/console cache:clear
php bin/console cache:warmup
echo -e "${GREEN}✓ Cache prêt${RESET}"

echo -e "\n${BOLD}${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo -e "${BOLD}${GREEN}  ✅ Installation terminée !                    ${RESET}"
echo -e "${BOLD}${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}\n"
echo -e "  ${BOLD}Lancer l'application :${RESET}"
echo -e "  ${BLUE}symfony serve${RESET}  →  http://localhost:8000\n"
echo -e "  ${BOLD}Comptes de test :${RESET}"
echo -e "  admin@ajbva.ci         / Admin@2024!    (Super Admin)"
echo -e "  bureau@ajbva.ci        / Bureau@2024!   (Bureau)"
echo -e "  vp.social@ajbva.ci     / VPSocial@2024! (VP Social)"
echo -e "  jean.kouassi@email.com / Membre@2024!   (Membre)\n"
