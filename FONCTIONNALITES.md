# 📋 Fonctionnalités - Application de Gestion de Chantiers (BTP)

## 🎯 Objectif du Projet

Créer une application universelle de gestion et suivi des chantiers (BTP) utilisée par plusieurs entreprises, permettant d'organiser les chantiers, suivre l'avancement, gérer les équipes, les matériaux, les dépenses, les rapports, etc.

L'application sera **multi-entreprises**, **multi-utilisateurs**, avec des **rôles et des permissions**.

---

## 📦 Modules à Développer

### A. 🔐 Authentification & Gestion des Entreprises

#### A.1. Création de compte utilisateur
- [ ] Inscription avec email et mot de passe
- [ ] Vérification d'email
- [ ] Réinitialisation de mot de passe
- [ ] Connexion / Déconnexion
- [ ] Gestion de profil utilisateur

#### A.2. Gestion des entreprises
- [ ] Création d'entreprise
- [ ] Modification des informations d'entreprise
- [ ] Logo et informations de l'entreprise
- [ ] Un utilisateur peut appartenir à plusieurs entreprises
- [ ] Basculement entre entreprises (si utilisateur multi-entreprises)

#### A.3. Invitation de collaborateurs
- [ ] Invitation par email
- [ ] Invitation par SMS
- [ ] Lien d'invitation unique
- [ ] Gestion des invitations en attente
- [ ] Annulation d'invitation

#### A.4. Rôles & Permissions
- [ ] **Admin** : Accès complet à toutes les fonctionnalités
- [ ] **Chef de chantier** : Gestion complète d'un ou plusieurs chantiers
- [ ] **Ingénieur** : Suivi technique et validation des travaux
- [ ] **Ouvrier** : Pointage, mise à jour d'avancement, photos
- [ ] **Comptable** : Gestion financière, dépenses, budgets
- [ ] **Superviseur** : Vue d'ensemble, rapports, validation
- [ ] Système de permissions granulaires par module
- [ ] Attribution de rôles par entreprise

---

### B. 🏗️ Gestion des Projets / Chantiers

#### B.1. Création et gestion de chantiers
- [ ] Créer un nouveau chantier
- [ ] Informations du chantier :
  - Nom du chantier
  - Description
  - Localisation GPS (coordonnées)
  - Adresse complète
  - Date de début prévue
  - Date de fin prévue
  - Budget initial
  - Responsable(s) assigné(s)
  - Client / Maître d'ouvrage
- [ ] Modifier un chantier existant
- [ ] Supprimer un chantier (avec restrictions selon rôle)
- [ ] Archiver un chantier terminé

#### B.2. Liste et filtres des chantiers
- [ ] Liste de tous les chantiers
- [ ] Filtres par :
  - Statut
  - Responsable
  - Entreprise
  - Date
  - Localisation
- [ ] Recherche de chantier
- [ ] Tri par colonnes
- [ ] Vue liste / Vue carte (géolocalisation)

#### B.3. Statuts des chantiers
- [ ] **Non démarré** : Chantier créé mais pas encore commencé
- [ ] **En cours** : Chantier actif
- [ ] **Terminé** : Chantier complété
- [ ] **Bloqué** : Chantier en pause (problème, attente, etc.)
- [ ] Historique des changements de statut
- [ ] Notifications lors du changement de statut

#### B.4. Assignation d'équipe
- [ ] Assigner une équipe complète au chantier
- [ ] Assigner des membres individuels
- [ ] Gérer les rôles au sein du chantier
- [ ] Voir la liste des membres assignés
- [ ] Retirer un membre d'un chantier

---

### C. 📊 Avancement des Travaux (Module Clé)

#### C.1. Mise à jour d'avancement
- [ ] Ajouter une mise à jour d'avancement
- [ ] Indiquer le pourcentage d'avancement global
- [ ] Indiquer le pourcentage par tâche/phase
- [ ] Date et heure de la mise à jour
- [ ] Auteur de la mise à jour

#### C.2. Médias (Photos / Vidéos)
- [ ] Upload de photos (multiple)
- [ ] Upload de vidéos
- [ ] Compression automatique des images
- [ ] Galerie de médias par chantier
- [ ] Légendes et descriptions pour chaque média
- [ ] Géolocalisation des photos (optionnel)
- [ ] Suppression de médias

#### C.3. Rapports texte et vocal
- [ ] Rapport texte libre
- [ ] Rapport vocal (enregistrement audio)
- [ ] Transcription automatique (optionnel)
- [ ] Format de rapport structuré
- [ ] Templates de rapports

#### C.4. Timeline du chantier
- [ ] Historique chronologique de toutes les mises à jour
- [ ] Filtres par type d'événement
- [ ] Vue timeline visuelle
- [ ] Export de l'historique

#### C.5. Suivi en temps réel
- [ ] Notifications en temps réel des mises à jour
- [ ] Vue d'ensemble de l'avancement
- [ ] Graphiques d'évolution de l'avancement
- [ ] Comparaison avec le planning initial

---

### D. 📦 Gestion des Matériaux

#### D.1. Liste des matériaux
- [ ] Créer une liste de matériaux pour un chantier
- [ ] Catalogue de matériaux (base de données)
- [ ] Catégories de matériaux :
  - Ciment, béton
  - Acier, ferraillage
  - Bois, charpente
  - Électricité
  - Plomberie
  - Peinture, finitions
  - Autres
- [ ] Informations par matériau :
  - Nom
  - Unité (kg, m², m³, pièce, etc.)
  - Prix unitaire
  - Fournisseur

#### D.2. Quantités
- [ ] Quantités prévues (planning initial)
- [ ] Quantités commandées
- [ ] Quantités livrées
- [ ] Quantités utilisées
- [ ] Quantités restantes (calcul automatique)
- [ ] Historique des mouvements

#### D.3. Importation de données
- [ ] Import depuis fichier Excel (.xlsx, .csv)
- [ ] Import via API
- [ ] Template Excel fourni
- [ ] Validation des données importées
- [ ] Gestion des erreurs d'import

#### D.4. Alertes matériaux
- [ ] **Stock presque fini** : Seuil configurable (ex: < 10%)
- [ ] **Surconsommation** : Dépasse le prévu de X%
- [ ] **Commande nécessaire** : Alerte pour commander
- [ ] Notifications par email / SMS
- [ ] Dashboard des alertes

#### D.5. Gestion du stock
- [ ] Stock global de l'entreprise
- [ ] Stock par chantier
- [ ] Transfert de matériaux entre chantiers
- [ ] Inventaire périodique

---

### E. 👷 Gestion des Employés / Ouvriers

#### E.1. Gestion des employés
- [ ] Ajouter un employé manuellement
- [ ] Informations employé :
  - Nom, prénom
  - Email, téléphone
  - Poste / Fonction
  - Compétences / Qualifications
  - Date d'embauche
  - Photo
- [ ] Modifier les informations
- [ ] Désactiver un employé
- [ ] Import depuis Excel

#### E.2. Pointage (Check-in / Check-out)
- [ ] Pointage d'arrivée (check-in)
- [ ] Pointage de départ (check-out)
- [ ] Géolocalisation du pointage (vérification présence sur chantier)
- [ ] Photo de pointage (optionnel)
- [ ] Pointage manuel par chef de chantier
- [ ] Historique des pointages
- [ ] Export des heures travaillées

#### E.3. Affectation aux chantiers
- [ ] Assigner un employé à un chantier
- [ ] Assigner plusieurs employés en une fois
- [ ] Dates d'affectation (début / fin)
- [ ] Voir les employés d'un chantier
- [ ] Voir les chantiers d'un employé

#### E.4. Main-d'œuvre
- [ ] Calcul de la main-d'œuvre utilisée par jour
- [ ] Calcul par chantier
- [ ] Calcul par employé
- [ ] Coût de la main-d'œuvre
- [ ] Graphiques d'évolution

---

### F. 💰 Dépenses & Budget

#### F.1. Déclaration de dépenses
- [ ] Créer une dépense
- [ ] Catégories de dépenses :
  - **Matériaux** : Achat de matériaux
  - **Transport** : Frais de transport, carburant
  - **Main-d'œuvre** : Salaires, heures supplémentaires
  - **Location machines** : Location d'engins, équipements
  - **Autres** : Divers, imprévus
- [ ] Informations de la dépense :
  - Montant
  - Date
  - Description
  - Chantier concerné
  - Catégorie
  - Fournisseur / Bénéficiaire
  - Mode de paiement

#### F.2. Upload de factures
- [ ] Upload de factures (photos ou PDF)
- [ ] OCR pour extraction automatique (optionnel)
- [ ] Association facture / dépense
- [ ] Galerie de factures
- [ ] Validation comptable

#### F.3. Suivi du budget
- [ ] Budget initial du chantier
- [ ] Budget alloué par catégorie
- [ ] Dépenses réelles
- [ ] Écart budget / réel
- [ ] Pourcentage d'utilisation du budget
- [ ] Alertes si dépassement

#### F.4. Graphiques et rapports financiers
- [ ] Graphique d'évolution des dépenses
- [ ] Répartition par catégorie (camembert)
- [ ] Comparaison budget / réel
- [ ] Prévisions de fin de projet
- [ ] Export Excel / PDF

---

### G. ✅ Tâches & Planning

#### G.1. Liste des tâches
- [ ] Créer une tâche pour un chantier
- [ ] Catégories de tâches :
  - Maçonnerie
  - Fondations
  - Électricité
  - Plomberie
  - Peinture
  - Finitions
  - Autres
- [ ] Informations de la tâche :
  - Titre
  - Description
  - Priorité (Basse, Normale, Haute, Urgente)
  - Date de début prévue
  - Date de fin prévue (deadline)
  - Durée estimée
  - Statut

#### G.2. Assignation de tâches
- [ ] Assigner une tâche à un employé
- [ ] Assigner à une équipe
- [ ] Réassignation de tâche
- [ ] Voir les tâches assignées à un employé

#### G.3. Avancement des tâches
- [ ] Statuts : À faire / En cours / Terminé / Bloqué
- [ ] Pourcentage d'avancement
- [ ] Commentaires sur la tâche
- [ ] Mise à jour de l'avancement

#### G.4. Alertes de retard
- [ ] Détection automatique des retards
- [ ] Alerte si deadline approche (ex: 2 jours avant)
- [ ] Alerte si deadline dépassée
- [ ] Notifications aux responsables
- [ ] Rapport des tâches en retard

#### G.5. Planning visuel
- [ ] Vue calendrier des tâches
- [ ] Vue Gantt (optionnel)
- [ ] Vue Kanban
- [ ] Filtres par employé, statut, priorité

---

### H. 📄 Rapports Automatiques

#### H.1. Rapport journalier automatique
- [ ] Génération automatique chaque jour
- [ ] Contenu du rapport :
  - **Météo** : Conditions météorologiques du jour
  - **Présence** : Liste des présents / absents
  - **Photos du jour** : Sélection automatique
  - **Dépenses du jour** : Toutes les dépenses enregistrées
  - **Avancement** : Mises à jour d'avancement
  - **Tâches** : Tâches réalisées / en cours
  - **Problèmes rencontrés**
- [ ] Envoi automatique par email aux responsables
- [ ] Format PDF téléchargeable

#### H.2. Rapport hebdomadaire
- [ ] Génération automatique chaque semaine
- [ ] Synthèse de la semaine :
  - Avancement global
  - Dépenses de la semaine
  - Présences
  - Problèmes majeurs
  - Prochaines étapes
- [ ] Envoi à la direction
- [ ] Export PDF

#### H.3. Rapports personnalisés
- [ ] Création de rapports personnalisés
- [ ] Sélection des données à inclure
- [ ] Période personnalisable
- [ ] Export PDF / Excel

---

### I. 💬 Chat Interne / Commentaires

#### I.1. Fil de discussion par chantier
- [ ] Chat dédié à chaque chantier
- [ ] Messages texte
- [ ] Pièces jointes (photos, documents)
- [ ] Emojis et réactions
- [ ] Historique des messages

#### I.2. Notifications
- [ ] Notification lors d'un nouveau message
- [ ] Notification lors d'une mention @nom
- [ ] Notifications par email (optionnel)
- [ ] Notifications push (mobile)

#### I.3. Mentions
- [ ] Système de mention @nom utilisateur
- [ ] Autocomplétion des noms
- [ ] Notification à l'utilisateur mentionné

#### I.4. Commentaires sur éléments
- [ ] Commentaires sur les mises à jour d'avancement
- [ ] Commentaires sur les tâches
- [ ] Commentaires sur les dépenses
- [ ] Thread de discussion

---

### J. 📊 Tableaux de Bord (Dashboards)

#### J.1. Dashboard Entreprise
- [ ] Vue d'ensemble de toutes les entreprises (pour super admin)
- [ ] Statistiques globales :
  - Nombre de chantiers actifs
  - Nombre d'employés
  - Budget total
  - Dépenses totales
- [ ] Graphiques :
  - Répartition des chantiers par statut
  - Évolution des dépenses
  - Répartition par type de chantier

#### J.2. Dashboard Chantier
- [ ] Vue d'ensemble d'un chantier spécifique
- [ ] Informations clés :
  - Avancement global (%)
  - Budget utilisé (%)
  - Équipe assignée
  - Prochaines échéances
- [ ] Graphiques :
  - **Avancement** : Courbe d'évolution dans le temps
  - **Dépenses** : Évolution et répartition
  - **Présences** : Taux de présence par jour
  - **Matériel** : Consommation vs prévu
- [ ] Alertes et notifications importantes
- [ ] Dernières activités

#### J.3. Widgets personnalisables
- [ ] Personnalisation du dashboard
- [ ] Ajout / suppression de widgets
- [ ] Réorganisation par glisser-déposer
- [ ] Sauvegarde de la configuration

#### J.4. Filtres et périodes
- [ ] Filtre par période (jour, semaine, mois, année)
- [ ] Filtre par chantier
- [ ] Comparaison de périodes
- [ ] Export des données

---

## 🔧 Fonctionnalités Techniques

### API & Backend (Laravel)
- [ ] API RESTful complète
- [ ] Authentification JWT ou Sanctum
- [ ] Validation des données
- [ ] Gestion des fichiers (upload, stockage)
- [ ] Notifications en temps réel (WebSockets / Pusher)
- [ ] Export PDF (DomPDF / Snappy)
- [ ] Export Excel (Maatwebsite Excel)
- [ ] Géolocalisation (Google Maps API)
- [ ] OCR pour factures (Tesseract / Google Vision)
- [ ] Queue pour tâches asynchrones
- [ ] Cache pour performances

### Frontend (Dashboard Laravel)
- [ ] Interface responsive (Bootstrap 5)
- [ ] Graphiques interactifs (Chart.js / ApexCharts)
- [ ] Upload de fichiers avec preview
- [ ] Notifications toast
- [ ] Modals et confirmations
- [ ] Filtres et recherche avancée
- [ ] Export de données
- [ ] Impression de rapports

### Mobile (Flutter - Phase 2)
- [ ] Application mobile Flutter
- [ ] Synchronisation avec l'API Laravel
- [ ] Mode hors ligne (stockage local)
- [ ] Géolocalisation GPS
- [ ] Appareil photo intégré
- [ ] Notifications push
- [ ] Pointage avec QR code (optionnel)

---

## 📅 Plan de Développement Suggéré

### Phase 1 : Fondations (Semaines 1-2)
1. Authentification & Entreprises
2. Gestion des chantiers (CRUD de base)
3. Rôles et permissions

### Phase 2 : Modules Core (Semaines 3-5)
1. Avancement des travaux
2. Gestion des matériaux
3. Gestion des employés et pointage

### Phase 3 : Financier (Semaines 6-7)
1. Dépenses & Budget
2. Rapports financiers

### Phase 4 : Organisation (Semaines 8-9)
1. Tâches & Planning
2. Rapports automatiques

### Phase 5 : Communication (Semaine 10)
1. Chat interne / Commentaires

### Phase 6 : Dashboards (Semaine 11)
1. Tableaux de bord complets
2. Graphiques et statistiques

### Phase 7 : Optimisation (Semaine 12)
1. Tests
2. Optimisations
3. Documentation

---

## 🎯 Priorités de Développement

### Priorité 1 (Essentiel)
- Authentification & Entreprises
- Gestion des chantiers
- Avancement des travaux
- Gestion des matériaux
- Pointage des employés

### Priorité 2 (Important)
- Dépenses & Budget
- Tâches & Planning
- Dashboards

### Priorité 3 (Amélioration)
- Rapports automatiques
- Chat interne
- Fonctionnalités avancées

---

## 📝 Notes Techniques

- **Base de données** : MySQL / PostgreSQL
- **Backend** : Laravel 12
- **Frontend Dashboard** : Blade + Bootstrap 5 (Modernize Template)
- **Mobile** : Flutter (Phase 2)
- **Stockage fichiers** : Local / S3
- **Notifications** : Laravel Notifications + Pusher
- **API** : Laravel API Resources

---

## ✅ Checklist de Démarrage

- [x] Projet Laravel créé
- [x] Template Modernize intégré
- [ ] Base de données créée
- [ ] Migrations créées
- [ ] Modèles Eloquent créés
- [ ] Contrôleurs créés
- [ ] Routes API définies
- [ ] Authentification configurée
- [ ] Première fonctionnalité développée

---

**Date de création** : 30 Novembre 2024  
**Version** : 1.0  
**Statut** : En développement

