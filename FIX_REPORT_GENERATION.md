# Correction de la génération de rapports

## Problèmes identifiés et corrigés

### 1. Gestion des erreurs de validation
- **Problème** : Les erreurs de validation retournaient un message générique
- **Solution** : Extraction et affichage des messages d'erreur détaillés

### 2. Validation des dates futures
- **Problème** : Les dates futures n'étaient pas rejetées côté serveur
- **Solution** : Ajout de validation `before_or_equal:today` pour les dates

### 3. Gestion des erreurs dans le repository Flutter
- **Problème** : Les erreurs de validation n'étaient pas correctement extraites
- **Solution** : Amélioration de `_handleError` pour extraire tous les messages d'erreur

### 4. Logs de débogage
- **Ajout** : Logs détaillés à chaque étape de la génération

## Comment déboguer

### 1. Vérifier les logs Laravel
```bash
cd /Users/mouhamadoulaminefaye/Desktop/PROJETS\ DEV/btp/chantix
tail -f storage/logs/laravel.log
```

### 2. Vérifier les logs Flutter
Dans l'application Flutter, activez le mode debug pour voir les logs dans la console.

### 3. Tester manuellement l'API
```bash
curl -X POST http://votre-serveur/api/v1/projects/1/reports/generate \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "journalier",
    "report_date": "2024-12-06"
  }'
```

### 4. Vérifier les permissions
```bash
# Vérifier que le répertoire reports existe et est accessible
ls -la storage/app/public/reports
chmod -R 755 storage/app/public/reports
```

### 5. Vérifier DomPDF
```bash
# Vérifier que DomPDF est installé
composer show barryvdh/laravel-dompdf
```

## Erreurs courantes et solutions

### Erreur : "Erreur lors de la génération du rapport"
1. Vérifier les logs Laravel pour le message exact
2. Vérifier que le projet existe et appartient à l'entreprise de l'utilisateur
3. Vérifier que la date est valide (pas dans le futur)
4. Vérifier les permissions du répertoire storage

### Erreur : "Erreur de validation"
1. Vérifier le format de la date (YYYY-MM-DD)
2. Vérifier que le type est "journalier" ou "hebdomadaire"
3. Pour les rapports hebdomadaires, vérifier que end_date >= report_date

### Erreur : "Erreur lors de la collecte des données"
1. Vérifier que les relations (attendances, expenses, tasks) existent
2. Vérifier les logs pour voir quelle relation pose problème

### Erreur : "Erreur lors de la génération du PDF"
1. Vérifier que les vues Blade existent (resources/views/reports/pdf/daily.blade.php et weekly.blade.php)
2. Vérifier que DomPDF peut accéder aux fichiers
3. Vérifier les permissions du répertoire storage

## Tests à effectuer

1. ✅ Générer un rapport journalier avec une date passée
2. ✅ Générer un rapport hebdomadaire avec des dates valides
3. ❌ Essayer de générer un rapport avec une date future (doit échouer avec un message clair)
4. ✅ Vérifier que les erreurs de validation affichent des messages clairs
5. ✅ Vérifier que les logs contiennent les informations nécessaires

## Prochaines étapes si le problème persiste

1. Activer le mode debug dans `.env` : `APP_DEBUG=true`
2. Vérifier les logs en temps réel pendant la génération
3. Tester l'endpoint directement avec Postman ou curl
4. Vérifier la configuration de DomPDF dans `config/dompdf.php`
5. Vérifier que toutes les dépendances sont installées : `composer install`
