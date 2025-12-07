# Test de l'API des rapports

## Problème
Les rapports sont visibles dans le backend web mais pas dans l'application mobile.

## Test de l'API

### 1. Tester l'endpoint directement

Remplacez `VOTRE_TOKEN` et `PROJECT_ID` par vos valeurs réelles :

```bash
curl -X GET "http://votre-serveur/api/v1/projects/PROJECT_ID/reports" \
  -H "Authorization: Bearer VOTRE_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json"
```

### 2. Vérifier la réponse

La réponse devrait être au format :

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "created_by": 1,
      "type": "journalier",
      "report_date": "2024-12-06",
      "end_date": null,
      "data": {...},
      "file_path": "reports/rapport-...",
      "created_at": "2024-12-06 10:00:00",
      "updated_at": "2024-12-06 10:00:00",
      "creator": {
        "id": 1,
        "name": "Nom",
        "email": "email@example.com"
      }
    }
  ]
}
```

### 3. Vérifier dans la base de données

```sql
-- Voir tous les rapports
SELECT id, project_id, type, report_date, created_at 
FROM reports 
ORDER BY created_at DESC 
LIMIT 10;

-- Voir les rapports d'un projet spécifique
SELECT id, project_id, type, report_date, created_at 
FROM reports 
WHERE project_id = VOTRE_PROJECT_ID
ORDER BY created_at DESC;
```

### 4. Vérifier les permissions

Assurez-vous que :
- L'utilisateur connecté dans l'app mobile a accès au projet
- Le `company_id` de l'utilisateur correspond au `company_id` du projet
- L'utilisateur n'est pas un super admin (qui pourrait avoir des permissions différentes)

## Corrections apportées

### 1. Format des dates dans l'API
- Les dates sont maintenant toujours retournées comme des strings au format 'Y-m-d'
- Les timestamps sont formatés comme 'Y-m-d H:i:s'

### 2. Parsing amélioré dans Flutter
- Gestion des différents formats de dates
- Gestion des erreurs de parsing sans arrêter tout le processus
- Logs détaillés pour identifier les problèmes

### 3. Gestion des relations
- Le créateur est maintenant inclus dans la réponse avec seulement les champs nécessaires

## Prochaines étapes

1. Testez l'API directement avec curl pour voir si les rapports sont retournés
2. Vérifiez les logs dans l'application mobile pour voir ce qui est reçu
3. Vérifiez que le project_id utilisé dans l'app correspond bien au project_id des rapports

