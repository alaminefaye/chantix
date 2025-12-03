# 📊 Analyse Complète du Projet Chantix

## 🎯 Vue d'Ensemble

**Chantix** est une application web de gestion de chantiers BTP (Bâtiment et Travaux Publics) développée avec Laravel 12. L'application permet de gérer plusieurs entreprises, leurs projets, équipes, matériaux, dépenses et rapports.

**Date de création** : Novembre 2024  
**Version actuelle** : 1.0.0  
**Statut** : Production Ready (Web) | En développement (Mobile)

---

## 🏗️ Architecture du Projet

### Backend (Laravel 12)
- **Framework** : Laravel 12.40.2
- **PHP** : 8.2.29
- **Base de données** : MySQL
- **Authentification** : Laravel Auth (Session-based)
- **Template** : Modernize (Bootstrap 5)

### Frontend Web
- **Template Engine** : Blade
- **CSS Framework** : Bootstrap 5
- **JavaScript** : jQuery
- **Graphiques** : ApexCharts, Chart.js
- **Cartes** : Leaflet
- **Icônes** : Tabler Icons

### Mobile (À développer)
- **Framework** : Flutter
- **Nom du projet** : `chantix-app`
- **API** : Laravel Sanctum (à configurer)

---

## 📦 Modules Développés

### 1. 🔐 Authentification & Sécurité

#### Fonctionnalités
- ✅ Inscription avec création automatique d'entreprise
- ✅ Connexion / Déconnexion
- ✅ Vérification d'email après inscription
- ✅ Réinitialisation de mot de passe (lien par email)
- ✅ Gestion complète du profil utilisateur
- ✅ Upload d'avatar utilisateur
- ✅ Changement de mot de passe
- ✅ **Super Admin** : Compte avec accès global (`admin@admin.com` / `passer123`)
- ✅ **Validation des comptes** : Les nouveaux utilisateurs doivent être validés par le Super Admin

#### Routes Web
```
GET  /login
POST /login
GET  /register
POST /register
GET  /forgot-password
POST /forgot-password
GET  /reset-password/{token}
POST /reset-password
GET  /email/verify
GET  /email/verify/{id}/{hash}
POST /email/verification-notification
POST /logout
GET  /profile
PUT  /profile
PUT  /profile/password
```

#### Modèles
- `User` : Utilisateurs avec `is_super_admin`, `is_verified`, `current_company_id`

---

### 2. 🏢 Gestion des Entreprises

#### Fonctionnalités
- ✅ Création et modification d'entreprise
- ✅ Upload de logo d'entreprise
- ✅ Multi-entreprises (un utilisateur peut appartenir à plusieurs entreprises)
- ✅ Basculement entre entreprises
- ✅ Système d'invitations par email avec tokens uniques
- ✅ Gestion des invitations (créer, voir, modifier, supprimer, renvoyer, accepter)
- ✅ **Création directe d'utilisateurs** (sans invitation par email)

#### Routes Web
```
GET    /companies
GET    /companies/create
POST   /companies
GET    /companies/{company}
GET    /companies/{company}/edit
PUT    /companies/{company}
POST   /companies/{company}/switch

GET    /companies/{company}/invitations
GET    /companies/{company}/invitations/create
POST   /companies/{company}/invitations
GET    /companies/{company}/invitations/{invitation}
GET    /companies/{company}/invitations/{invitation}/edit
PUT    /companies/{company}/invitations/{invitation}
POST   /companies/{company}/invitations/{invitation}/resend
DELETE /companies/{company}/invitations/{invitation}
GET    /invitations/accept/{token}
```

#### Modèles
- `Company` : Entreprises
- `Invitation` : Invitations avec statuts (pending, accepted, cancelled, expired)

---

### 3. 👥 Système de Rôles & Permissions

#### Rôles Disponibles
1. **Super Admin** : Accès global à toutes les entreprises et fonctionnalités
2. **Admin** : Accès complet dans son entreprise
3. **Chef de Chantier** : Gestion complète des chantiers
4. **Ingénieur** : Suivi technique (vue seule)
5. **Ouvrier** : Pointage et mises à jour
6. **Comptable** : Gestion financière (vue seule)
7. **Superviseur** : Vue d'ensemble (vue seule)

#### Permissions
- ✅ Système de permissions granulaires par rôle
- ✅ Méthodes `hasPermission()` et `canManageProject()` dans le modèle User
- ✅ Middleware `CheckPermission` pour protéger les routes
- ✅ Middleware `CheckUserVerified` pour vérifier la validation des comptes
- ✅ Attribution automatique du rôle Admin lors de la création d'entreprise

#### Modèles
- `Role` : Rôles avec permissions
- `User` : Relation many-to-many avec `Role` via `company_user_role`

---

### 4. 🏗️ Gestion des Projets / Chantiers

#### Fonctionnalités
- ✅ CRUD complet des projets
- ✅ Informations complètes : nom, description, GPS, dates, budget, client
- ✅ Filtres avancés : statut, responsable, dates, recherche
- ✅ Vue liste et vue carte (géolocalisation avec Leaflet)
- ✅ 4 statuts : Non démarré, En cours, Terminé, Bloqué
- ✅ Historique des changements de statut avec raison
- ✅ Timeline du projet (chronologie des événements)
- ✅ Assignation d'équipe aux projets
- ✅ Assignation de matériaux aux projets
- ✅ Galerie de médias par projet

#### Routes Web
```
GET    /projects
GET    /projects/create
POST   /projects
GET    /projects/{project}
GET    /projects/{project}/edit
PUT    /projects/{project}
DELETE /projects/{project}
GET    /projects/{project}/timeline
GET    /projects/{project}/gallery
```

#### Modèles
- `Project` : Projets avec relations vers Company, User, Employee, Material
- `ProjectStatusHistory` : Historique des changements de statut
- `ProjectEmployee` : Table pivot pour employés-projets
- `ProjectMaterial` : Table pivot pour matériaux-projets

---

### 5. 📊 Avancement des Travaux

#### Fonctionnalités
- ✅ Mises à jour d'avancement avec pourcentage
- ✅ Upload multiple de photos et vidéos (max 50MB par vidéo)
- ✅ Rapports texte et audio
- ✅ Géolocalisation des mises à jour
- ✅ Galerie de médias par projet
- ✅ Graphique d'évolution de l'avancement dans le temps

#### Routes Web
```
GET    /projects/{project}/progress
GET    /projects/{project}/progress/create
POST   /projects/{project}/progress
GET    /projects/{project}/progress/{progressUpdate}
DELETE /projects/{project}/progress/{progressUpdate}
```

#### Modèles
- `ProgressUpdate` : Mises à jour d'avancement avec photos, vidéos, audio, GPS

---

### 6. 📦 Gestion des Matériaux

#### Fonctionnalités
- ✅ Catalogue de matériaux
- ✅ CRUD complet
- ✅ Gestion des stocks (prévu, commandé, livré, utilisé, restant)
- ✅ Alertes de stock faible
- ✅ Détection de surconsommation
- ✅ Import Excel avec template
- ✅ Transfert de matériaux entre chantiers
- ✅ Suivi par projet
- ✅ Champ "Unité" en select (kg, m, m², L, Pièce, etc.)

#### Routes Web
```
GET    /materials
GET    /materials/create
POST   /materials
GET    /materials/{material}
GET    /materials/{material}/edit
PUT    /materials/{material}
DELETE /materials/{material}
GET    /materials/import
POST   /materials/import
GET    /materials/template/download
POST   /projects/{project}/materials/add
PUT    /projects/{project}/materials/{material}/update
GET    /projects/{project}/materials/{material}/transfer
POST   /projects/{project}/materials/{material}/transfer
```

#### Modèles
- `Material` : Matériaux avec stock, unité, seuil d'alerte

---

### 7. 👷 Gestion des Employés

#### Fonctionnalités
- ✅ CRUD complet des employés
- ✅ Informations complètes (nom, email, téléphone, poste, etc.)
- ✅ **Génération automatique du numéro d'employé**
- ✅ Import Excel avec template
- ✅ Affectation aux projets avec rôles
- ✅ Pointage (check-in / check-out)
- ✅ Photo de pointage optionnelle (check-in et check-out)
- ✅ Géolocalisation du pointage
- ✅ Calcul automatique des heures travaillées et heures supplémentaires
- ✅ Gestion des absences avec raisons
- ✅ **Suppression d'employés** (delete)

#### Routes Web
```
GET    /employees
GET    /employees/create
POST   /employees
GET    /employees/{employee}
GET    /employees/{employee}/edit
PUT    /employees/{employee}
DELETE /employees/{employee}
GET    /employees/import
POST   /employees/import
GET    /employees/template/download
POST   /projects/{project}/employees/assign
POST   /projects/{project}/employees/{employee}/remove
```

#### Routes Pointage
```
GET    /projects/{project}/attendances
GET    /projects/{project}/attendances/create
POST   /projects/{project}/attendances/check-in
POST   /projects/{project}/attendances/{attendance}/check-out
POST   /projects/{project}/attendances/absence
PUT    /projects/{project}/attendances/{attendance}
DELETE /projects/{project}/attendances/{attendance}
```

#### Modèles
- `Employee` : Employés avec numéro, poste, taux horaire
- `Attendance` : Pointages avec check-in, check-out, photos, GPS, heures

---

### 8. 💰 Dépenses & Budget

#### Fonctionnalités
- ✅ Déclaration de dépenses
- ✅ 5 catégories : Matériaux, Transport, Main-d'œuvre, Location, Autres
- ✅ Upload de factures (PDF, images)
- ✅ Suivi du budget par projet
- ✅ Graphiques financiers (camembert par type, évolution mensuelle)
- ✅ Alertes de dépassement de budget
- ✅ Devise : **FCFA** (au lieu de Euro)

#### Routes Web
```
GET    /projects/{project}/expenses
GET    /projects/{project}/expenses/create
POST   /projects/{project}/expenses
GET    /projects/{project}/expenses/{expense}
GET    /projects/{project}/expenses/{expense}/edit
PUT    /projects/{project}/expenses/{expense}
DELETE /projects/{project}/expenses/{expense}
```

#### Modèles
- `Expense` : Dépenses avec catégorie, montant, facture, matériau, employé

---

### 9. ✅ Tâches & Planning

#### Fonctionnalités
- ✅ CRUD complet des tâches
- ✅ Catégories de tâches (maçonnerie, fondations, électricité, etc.)
- ✅ Statuts : À faire, En cours, Terminé, Bloqué
- ✅ Priorités : Basse, Moyenne, Haute, Urgente
- ✅ Assignation aux employés
- ✅ Suivi de l'avancement
- ✅ Détection des retards
- ✅ Vue Calendrier
- ✅ Vue Kanban

#### Routes Web
```
GET    /projects/{project}/tasks
GET    /projects/{project}/tasks/create
POST   /projects/{project}/tasks
GET    /projects/{project}/tasks/{task}
GET    /projects/{project}/tasks/{task}/edit
PUT    /projects/{project}/tasks/{task}
DELETE /projects/{project}/tasks/{task}
```

#### Modèles
- `Task` : Tâches avec catégorie, statut, priorité, dates, assignation

---

### 10. 📄 Rapports Automatiques

#### Fonctionnalités
- ✅ Rapport journalier (PDF)
- ✅ Rapport hebdomadaire (PDF)
- ✅ Export Excel pour rapports journaliers et hebdomadaires
- ✅ Historique des rapports générés
- ✅ Données complètes : présences, dépenses, avancement, tâches

#### Routes Web
```
GET    /projects/{project}/reports
GET    /projects/{project}/reports/daily
GET    /projects/{project}/reports/weekly
GET    /projects/{project}/reports/daily/excel
GET    /projects/{project}/reports/weekly/excel
```

#### Modèles
- `Report` : Rapports générés avec type, période, données

---

### 11. 💬 Chat Interne / Commentaires

#### Fonctionnalités
- ✅ Système de commentaires par projet
- ✅ Threading (réponses aux commentaires)
- ✅ Mentions d'utilisateurs (@nom)
- ✅ Pièces jointes (photos, PDF, documents)
- ✅ Prévisualisation des fichiers avant envoi
- ✅ Affichage des pièces jointes dans les commentaires et réponses

#### Routes Web
```
GET    /projects/{project}/comments
POST   /projects/{project}/comments
DELETE /projects/{project}/comments/{comment}
```

#### Modèles
- `Comment` : Commentaires avec parent_id pour threading, pièces jointes

---

### 12. 📊 Dashboard

#### Fonctionnalités
- ✅ Statistiques principales (projets, budget, avancement)
- ✅ Graphiques interactifs (ApexCharts)
- ✅ Répartition des projets par statut
- ✅ Projets récents
- ✅ Recherche globale (projets, matériaux, employés, tâches)
- ✅ Affichage des résultats par catégorie

#### Routes Web
```
GET    /dashboard
```

#### Données Affichées
- Total projets
- Projets actifs
- Budget total (en FCFA)
- Avancement moyen
- Répartition par statut (graphique)
- Liste des projets récents

---

### 13. 🔔 Notifications

#### Fonctionnalités
- ✅ Notifications en temps réel
- ✅ Compteur de notifications non lues
- ✅ Marquer comme lu / tout marquer comme lu
- ✅ Affichage dans le header

#### Routes Web
```
GET    /notifications
POST   /notifications/{notification}/read
POST   /notifications/read-all
GET    /api/notifications/unread-count
GET    /api/notifications/latest
```

#### Modèles
- `Notification` : Notifications avec type, données, lu/non lu

---

### 14. 👑 Super Admin

#### Fonctionnalités
- ✅ Accès global à toutes les entreprises
- ✅ Validation des nouveaux comptes utilisateurs
- ✅ Menu "Validation Utilisateurs"
- ✅ Menu "Entreprises" (visible uniquement pour Super Admin)
- ✅ Peut valider ou rejeter les comptes en attente

#### Routes Web
```
GET    /admin/users-validation
POST   /admin/users/{user}/verify
POST   /admin/users/{user}/reject
```

#### Identifiants
- **Email** : `admin@admin.com`
- **Mot de passe** : `passer123`

---

## 🗄️ Structure de la Base de Données

### Tables Principales
- `users` : Utilisateurs (avec `is_super_admin`, `is_verified`, `current_company_id`)
- `companies` : Entreprises
- `roles` : Rôles avec permissions
- `company_user_role` : Table pivot utilisateurs-entreprises-rôles
- `projects` : Projets/Chantiers
- `materials` : Matériaux
- `employees` : Employés
- `expenses` : Dépenses
- `tasks` : Tâches
- `progress_updates` : Mises à jour d'avancement
- `attendances` : Pointages
- `comments` : Commentaires/Chat
- `notifications` : Notifications
- `invitations` : Invitations
- `project_employees` : Table pivot projets-employés
- `project_materials` : Table pivot projets-matériaux
- `project_status_history` : Historique des statuts

---

## 🔌 API à Développer pour Mobile

### Authentification
```
POST   /api/login
POST   /api/logout
POST   /api/register
POST   /api/forgot-password
POST   /api/reset-password
GET    /api/user
PUT    /api/user
PUT    /api/user/password
```

### Entreprises
```
GET    /api/companies
GET    /api/companies/{id}
POST   /api/companies/{id}/switch
```

### Projets
```
GET    /api/projects
GET    /api/projects/{id}
POST   /api/projects
PUT    /api/projects/{id}
DELETE /api/projects/{id}
GET    /api/projects/{id}/timeline
GET    /api/projects/{id}/gallery
```

### Matériaux
```
GET    /api/materials
GET    /api/materials/{id}
POST   /api/materials
PUT    /api/materials/{id}
DELETE /api/materials/{id}
```

### Employés
```
GET    /api/employees
GET    /api/employees/{id}
POST   /api/employees
PUT    /api/employees/{id}
DELETE /api/employees/{id}
```

### Pointage
```
POST   /api/projects/{id}/attendances/check-in
POST   /api/projects/{id}/attendances/{attendance}/check-out
GET    /api/projects/{id}/attendances
```

### Avancement
```
GET    /api/projects/{id}/progress
POST   /api/projects/{id}/progress
DELETE /api/projects/{id}/progress/{progressUpdate}
```

### Dépenses
```
GET    /api/projects/{id}/expenses
POST   /api/projects/{id}/expenses
PUT    /api/projects/{id}/expenses/{expense}
DELETE /api/projects/{id}/expenses/{expense}
```

### Tâches
```
GET    /api/projects/{id}/tasks
POST   /api/projects/{id}/tasks
PUT    /api/projects/{id}/tasks/{task}
DELETE /api/projects/{id}/tasks/{task}
```

### Commentaires
```
GET    /api/projects/{id}/comments
POST   /api/projects/{id}/comments
DELETE /api/projects/{id}/comments/{comment}
```

### Dashboard
```
GET    /api/dashboard
```

### Notifications
```
GET    /api/notifications
POST   /api/notifications/{id}/read
POST   /api/notifications/read-all
GET    /api/notifications/unread-count
```

---

## 📱 Plan de Développement Mobile (Flutter)

### Phase 1 : Configuration & Authentification
1. ✅ Créer le projet Flutter `chantix-app`
2. ⏳ Configuration de l'architecture (Clean Architecture / MVVM)
3. ⏳ Configuration de Laravel Sanctum pour l'API
4. ⏳ Module d'authentification (login, register, logout)
5. ⏳ Gestion du token et stockage local
6. ⏳ Écran de profil utilisateur

### Phase 2 : Dashboard & Navigation
1. ⏳ Écran Dashboard avec statistiques
2. ⏳ Navigation principale (Bottom Navigation / Drawer)
3. ⏳ Sélection d'entreprise
4. ⏳ Notifications push

### Phase 3 : Projets
1. ⏳ Liste des projets
2. ⏳ Détails d'un projet
3. ⏳ Création/Modification de projet
4. ⏳ Carte avec géolocalisation
5. ⏳ Timeline du projet

### Phase 4 : Pointage
1. ⏳ Check-in avec photo et GPS
2. ⏳ Check-out avec photo et GPS
3. ⏳ Historique des pointages
4. ⏳ Gestion des absences

### Phase 5 : Avancement
1. ⏳ Création de mise à jour d'avancement
2. ⏳ Upload de photos/vidéos
3. ⏳ Enregistrement audio
4. ⏳ Géolocalisation
5. ⏳ Galerie de médias

### Phase 6 : Matériaux & Employés
1. ⏳ Liste des matériaux
2. ⏳ Gestion des stocks
3. ⏳ Liste des employés
4. ⏳ Détails employé

### Phase 7 : Dépenses & Tâches
1. ⏳ Déclaration de dépenses
2. ⏳ Upload de factures
3. ⏳ Liste des tâches
4. ⏳ Création/Modification de tâches

### Phase 8 : Communication
1. ⏳ Chat/Commentaires
2. ⏳ Mentions
3. ⏳ Pièces jointes

### Phase 9 : Rapports
1. ⏳ Consultation des rapports
2. ⏳ Export PDF/Excel

### Phase 10 : Mode Hors Ligne
1. ⏳ Stockage local (SQLite/Hive)
2. ⏳ Synchronisation automatique
3. ⏳ Gestion des conflits

---

## 🛠️ Technologies à Utiliser (Mobile)

### Flutter Packages Recommandés
- `http` ou `dio` : Requêtes HTTP
- `shared_preferences` : Stockage local simple
- `sqflite` ou `hive` : Base de données locale
- `provider` ou `bloc` : Gestion d'état
- `get_it` : Injection de dépendances
- `image_picker` : Sélection d'images
- `camera` : Appareil photo
- `geolocator` : Géolocalisation
- `permission_handler` : Gestion des permissions
- `flutter_local_notifications` : Notifications locales
- `firebase_messaging` : Notifications push
- `file_picker` : Sélection de fichiers
- `path_provider` : Chemins de fichiers
- `flutter_pdfview` : Affichage PDF
- `url_launcher` : Ouvrir des URLs
- `flutter_map` ou `google_maps_flutter` : Cartes
- `cached_network_image` : Images en cache
- `flutter_sound` : Enregistrement audio
- `video_player` : Lecture vidéo

---

## 📝 Notes Importantes

### Devise
- **FCFA** est utilisé partout dans l'application (remplacement de Euro)

### Permissions
- Les menus sont masqués selon les rôles
- Les contrôleurs bloquent l'accès direct via URL
- Chaque rôle ne voit que ce qui lui est nécessaire

### Validation des Comptes
- Les nouveaux utilisateurs doivent être validés par le Super Admin
- Les utilisateurs non vérifiés ne peuvent pas se connecter
- Message spécifique lors de la tentative de connexion

### Super Admin
- Accès global à toutes les entreprises
- Peut valider/rejeter les comptes
- Menu "Entreprises" visible uniquement pour Super Admin

### Génération Automatique
- Numéro d'employé généré automatiquement

### Suppression
- Les employés peuvent être supprimés (delete)
- Les invitations peuvent être supprimées (delete)

---

## 🚀 Prochaines Étapes

1. ✅ Créer le projet Flutter `chantix-app`
2. ⏳ Configurer Laravel Sanctum pour l'API
3. ⏳ Développer les routes API dans Laravel
4. ⏳ Commencer le développement mobile module par module
5. ⏳ Tester la synchronisation entre web et mobile

---

**Dernière mise à jour** : Décembre 2024

