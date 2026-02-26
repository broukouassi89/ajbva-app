# AJBVA — Application de Gestion Associative
## Symfony 7 · PHP 8.2+ · SQLite

Plateforme complète de gestion de l'**Association des Jeunes de Botro et Villages Avoisinants**.

---

## 🚀 Installation rapide

### Prérequis
- PHP 8.2 ou supérieur
- Composer
- Extensions PHP : `pdo`, `pdo_sqlite`, `intl`, `ctype`, `iconv`

### Étapes

```bash
# 1. Installer les dépendances (sans les scripts post-install)
composer install --no-scripts

# 2. Configurer l'environnement
cp .env .env.local
# Éditer .env.local si nécessaire (DATABASE_URL, APP_SECRET)

# 3. OU utiliser le script d'installation automatique (recommandé)
bash install.sh

Accès : **http://localhost:8000**

---

## 🧹 Maintenance et Importation

### Remise à zéro de la base (Production)
Pour vider toutes les données et repartir sur une base propre :
- **Windows** : Lancez `reset_db.bat`
- **Mac/Linux** : Lancez `reset_db.sh`

### Importation massive de membres
1. Préparez vos données dans le fichier **`import_membres.csv`** (ouvrez-le avec Excel, remplissez-le et enregistrez-le au format CSV avec séparateur point-virgule `;`).
2. Lancez la commande d'importation :
```bash
php bin/console app:import-membres import_membres.csv
```

---

## 🔐 Comptes de démonstration

| Rôle              | Email                      | Mot de passe    | Permissions |
|-------------------|---------------------------|-----------------|-------------|
| Super Administrateur | admin@ajbva.ci          | Admin@2024!     | Tout |
| Bureau / Trésorier  | bureau@ajbva.ci          | Bureau@2024!    | Membres, cotisations, cas sociaux |
| VP Affaires Sociales | vp.social@ajbva.ci      | VPSocial@2024!  | Cas sociaux |
| Membre              | jean.kouassi@email.com   | Membre@2024!    | Lecture seule |

---

## 📋 Fonctionnalités

### 🏠 Tableau de bord
- **5 KPIs** en temps réel : membres actifs, cotisations du mois, solde caisse, cas sociaux en attente
- **Graphique évolution** des cotisations mensuelles (interactif, changement d'année)
- **Graphique répartition** membres par statut (doughnut)
- **Graphique par village** (barre horizontale)
- **Graphique par genre** (doughnut)
- **Graphique par type** de cotisation (pie)
- Liste des derniers membres et dernières transactions

### 👥 Gestion des membres
- **Liste filtrable** (recherche, statut, genre, village, famille)
- **Fiche complète** avec photo, âge calculé, ancienneté formatée
- **Création** avec aperçu carte membre en temps réel, upload photo
- **Modification** avec conservation photo
- Gestion des statuts (Actif/Inactif/Ancien Membre)
- Graphique cotisations mensuelles par membre

### 💰 Cotisations
- Enregistrement de paiements (mensuelle, exceptionnelle, sociale, adhésion)
- **Génération automatique** du numéro de reçu
- **Rapport annuel** avec progression individuelle et graphiques
- Filtrage par type, statut, année

### ❤️ Affaires Sociales
- 6 types d'événements (Décès, Mariage, Naissance)
- **Calcul automatique** du montant d'assistance
- **Génération automatique** des cotisations sociales (1 500 F CFA × membres actifs)
- Workflow de validation (En attente → Validée → Payée)

### 📁 Projets
- Suivi du financement avec barre de progression
- Calcul des bénéfices
- Statuts (En cours, Terminé, Annulé)

### 🏛️ Patrimoine
- Inventaire des biens associatifs
- Valeur totale calculée
- Lien avec les projets

---

## 🏗️ Architecture technique

```
src/
├── Controller/          # Controllers Symfony (REST + HTML)
│   ├── DashboardController.php
│   ├── MembreController.php
│   ├── CotisationController.php
│   ├── CasSocialController.php
│   ├── ProjetPatrimoineController.php
│   └── SecurityController.php
├── Entity/              # Entités Doctrine ORM
│   ├── Membre.php       # Propriétés calculées (âge, ancienneté)
│   ├── User.php         # Authentification Symfony Security
│   ├── Cotisation.php
│   ├── CasSocial.php
│   ├── Projet.php
│   └── Patrimoine.php
├── Repository/          # Requêtes SQL métier
├── Service/
│   ├── MembreService.php   # Upload photo, création membre
│   └── FinanceService.php  # Calculs financiers, caisse
├── Form/
│   └── MembreType.php
└── DataFixtures/
    └── AppFixtures.php  # Données de démonstration

templates/
├── base.html.twig       # Layout principal avec sidebar
├── security/login.html.twig
├── dashboard/index.html.twig   # 5 graphiques Chart.js
├── membre/              # CRUD + fiche détaillée
├── cotisation/          # Liste + rapport annuel graphique
├── cas_social/          # Déclaration + workflow
├── projet/              # Cartes projets
└── patrimoine/          # Inventaire

public/
├── images/              # Avatars SVG par défaut
└── uploads/photos/      # Photos membres uploadées
```

---

## 📊 Règles métier

| Paramètre | Valeur |
|-----------|--------|
| Carte d'adhésion | 5 000 F CFA |
| Cotisation mensuelle | 1 500 F CFA |
| Objectif annuel | 18 000 F CFA (12 mois) |
| Cotisation sociale | 1 500 F CFA (par événement) |
| Assistance Décès membre | 100 000 F CFA |
| Assistance Décès conjoint | 75 000 F CFA |
| Assistance Décès enfant | 50 000 F CFA |
| Assistance Décès père/mère | nb enfants actifs × 15 000 F CFA |
| Assistance Mariage | 25 000 F CFA |
| Assistance Naissance | 10 000 F CFA |

---

## 🔧 Base de données alternative (MySQL/PostgreSQL)

Dans `.env.local` :
```dotenv
# MySQL
DATABASE_URL="mysql://user:password@127.0.0.1:3306/ajbva?serverVersion=8.0&charset=utf8mb4"

# PostgreSQL
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/ajbva?serverVersion=16"
```

---

## 🔒 Hiérarchie des rôles

```
ROLE_SUPER_ADMIN
    └── ROLE_ADMIN
            └── ROLE_BUREAU
                    └── ROLE_VP_SOCIAL
                                └── ROLE_MEMBRE
                                        └── ROLE_USER
```

---

Développé avec ❤️ pour l'AJBVA — Symfony 7 · Chart.js 4 · Doctrine ORM 3
