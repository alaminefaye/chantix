# 🏗️ Chantix - Application de Gestion de Chantiers BTP

Application universelle de gestion et suivi des chantiers (BTP) permettant d'organiser les chantiers, suivre l'avancement, gérer les équipes, les matériaux, les dépenses, les rapports, etc.

## 📋 Description

**Chantix** est une application web et mobile (à venir) de gestion de chantiers BTP, conçue pour être utilisée par plusieurs entreprises. Elle permet une gestion complète des projets de construction avec un suivi en temps réel.

### Caractéristiques principales

- ✅ **Multi-entreprises** : Une seule application pour plusieurs entreprises
- ✅ **Multi-utilisateurs** : Gestion des équipes avec rôles et permissions
- ✅ **Suivi en temps réel** : Mises à jour instantanées de l'avancement
- ✅ **Gestion complète** : Chantiers, matériaux, employés, dépenses, rapports
- ✅ **Interface moderne** : Dashboard responsive avec template Modernize

## 🚀 Installation

### Prérequis

- PHP >= 8.2
- Composer
- MySQL / PostgreSQL
- Node.js & NPM (pour les assets)

### Installation

1. **Cloner le projet** (ou utiliser le projet existant)
```bash
cd /Users/mouhamadoulaminefaye/Desktop/PROJETS\ DEV/btp/chantix
```

2. **Installer les dépendances**
```bash
composer install
npm install
```

3. **Configurer l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurer la base de données dans `.env`**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chantix
DB_USERNAME=root
DB_PASSWORD=
```

5. **Créer la base de données**
```bash
php artisan migrate
```

6. **Lancer le serveur de développement**
```bash
php artisan serve
```

L'application sera accessible sur : `http://127.0.0.1:8000`

## 📁 Structure du Projet

```
chantix/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # Contrôleurs
│   │   └── Middleware/       # Middleware
│   ├── Models/               # Modèles Eloquent
│   └── ...
├── database/
│   ├── migrations/           # Migrations
│   └── seeders/             # Seeders
├── public/
│   └── assets/              # Assets du template Modernize
├── resources/
│   ├── views/               # Vues Blade
│   │   ├── layouts/         # Layouts
│   │   ├── dashboard/       # Pages dashboard
│   │   ├── auth/            # Pages authentification
│   │   └── ui/              # Pages UI components
│   └── ...
├── routes/
│   ├── web.php              # Routes web
│   └── api.php              # Routes API (à venir)
├── FONCTIONNALITES.md       # Documentation complète des fonctionnalités
└── README.md                # Ce fichier
```

## 📚 Documentation

### Fonctionnalités

Consultez le fichier **[FONCTIONNALITES.md](./FONCTIONNALITES.md)** pour la liste complète et détaillée de toutes les fonctionnalités à développer.

### Modules principaux

1. **Authentification & Entreprises**
   - Gestion des utilisateurs
   - Gestion des entreprises
   - Rôles et permissions

2. **Gestion des Chantiers**
   - Création et suivi des chantiers
   - Géolocalisation
   - Statuts et workflow

3. **Avancement des Travaux**
   - Mises à jour d'avancement
   - Photos et vidéos
   - Rapports texte/vocal
   - Timeline

4. **Gestion des Matériaux**
   - Catalogue de matériaux
   - Suivi des quantités
   - Alertes de stock

5. **Gestion des Employés**
   - Pointage (check-in/check-out)
   - Affectation aux chantiers
   - Suivi de la main-d'œuvre

6. **Dépenses & Budget**
   - Déclaration de dépenses
   - Upload de factures
   - Suivi budgétaire

7. **Tâches & Planning**
   - Gestion des tâches
   - Planning visuel
   - Alertes de retard

8. **Rapports Automatiques**
   - Rapports journaliers
   - Rapports hebdomadaires
   - Export PDF/Excel

9. **Chat Interne**
   - Discussion par chantier
   - Mentions et notifications

10. **Tableaux de Bord**
    - Dashboard entreprise
    - Dashboard chantier
    - Graphiques et statistiques

## 🛠️ Technologies Utilisées

### Backend
- **Laravel 12** : Framework PHP
- **MySQL/PostgreSQL** : Base de données
- **Sanctum** : Authentification API

### Frontend
- **Blade** : Moteur de templates Laravel
- **Bootstrap 5** : Framework CSS (via template Modernize)
- **ApexCharts** : Graphiques
- **jQuery** : JavaScript
- **Tabler Icons** : Icônes

### À venir (Phase 2)
- **Flutter** : Application mobile
- **WebSockets** : Notifications en temps réel

## 📊 Routes Disponibles

### Web Routes

- `/` → Redirige vers le dashboard
- `/dashboard` → Page principale
- `/login` → Page de connexion
- `/register` → Page d'inscription
- `/ui/buttons` → Composants boutons
- `/ui/alerts` → Composants alertes
- `/ui/card` → Composants cartes
- `/ui/forms` → Composants formulaires
- `/ui/typography` → Typographie
- `/ui/icons` → Icônes
- `/sample-page` → Page exemple

### API Routes (À venir)

Les routes API seront documentées dans `routes/api.php` une fois développées.

## 🎯 Prochaines Étapes

1. **Créer les migrations** pour toutes les tables
2. **Développer les modèles** Eloquent
3. **Créer les contrôleurs** pour chaque module
4. **Développer l'API** RESTful
5. **Créer les vues** pour chaque fonctionnalité
6. **Implémenter l'authentification** complète
7. **Développer les fonctionnalités** une par une selon les priorités

Voir **[FONCTIONNALITES.md](./FONCTIONNALITES.md)** pour le plan de développement détaillé.

## 👥 Rôles et Permissions

L'application supporte plusieurs rôles :

- **Admin** : Accès complet
- **Chef de chantier** : Gestion complète des chantiers
- **Ingénieur** : Suivi technique
- **Ouvrier** : Pointage et mises à jour
- **Comptable** : Gestion financière
- **Superviseur** : Vue d'ensemble et validation

## 📝 License

Ce projet est développé pour un usage privé.

## 📞 Support

Pour toute question ou problème, consultez la documentation dans `FONCTIONNALITES.md`.

---

**Version** : 1.0.0  
**Dernière mise à jour** : 30 Novembre 2024  
**Statut** : En développement
# chantix
