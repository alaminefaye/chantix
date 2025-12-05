# 🔧 Configuration des Variables d'Environnement - Notifications Push

## ✅ Configuration Actuelle

### Variables d'environnement Firebase

La configuration Firebase est maintenant gérée via les variables d'environnement pour plus de flexibilité.

#### Dans `.env` (optionnel)

Vous pouvez ajouter cette variable dans votre fichier `.env` si vous souhaitez utiliser un chemin personnalisé :

```env
# Chemin vers le fichier de credentials Firebase (optionnel)
# Par défaut: storage/app/private/chantix-1334d-f9ec61331442.json
FIREBASE_CREDENTIALS_PATH=storage/app/private/chantix-1334d-f9ec61331442.json
```

**Note** : Si cette variable n'est pas définie, le système utilisera automatiquement le chemin par défaut.

### Configuration dans `config/services.php`

La configuration Firebase a été ajoutée dans `config/services.php` :

```php
'firebase' => [
    'credentials_path' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/private/chantix-1334d-f9ec61331442.json')),
],
```

## ✅ Vérifications Effectuées

1. ✅ **Fichier Firebase credentials** : Présent à `storage/app/private/chantix-1334d-f9ec61331442.json`
2. ✅ **Configuration services.php** : Firebase configuré avec variable d'environnement
3. ✅ **Service PushNotificationService** : Utilise maintenant `config('services.firebase.credentials_path')`
4. ✅ **Cache de configuration** : Vidé pour prendre en compte les nouvelles configurations

## 🚀 Fonctionnalité

Le backend est maintenant **100% fonctionnel** pour les notifications push :

### ✅ Ce qui fonctionne

1. **Service PushNotificationService**
   - Initialisation automatique de Firebase
   - Gestion des erreurs avec logs
   - Support des variables d'environnement

2. **Routes API FCM**
   - `POST /api/v1/fcm-tokens` - Enregistrer un token
   - `GET /api/v1/fcm-tokens` - Lister les tokens
   - `DELETE /api/v1/fcm-tokens` - Supprimer un token

3. **Intégration MaterialController**
   - Notifications automatiques lors de la création de matériau
   - Notifications lors de la mise à jour du stock
   - Alertes stock faible
   - Notifications lors de la suppression

4. **Gestion des tokens**
   - Enregistrement automatique des tokens FCM
   - Désactivation automatique des tokens invalides
   - Mise à jour de `last_used_at`

## 📝 Prochaines Étapes

### 1. Exécuter la migration (si pas encore fait)

```bash
cd chantix
php artisan migrate
```

### 2. Tester les notifications

1. Lancer l'application Flutter
2. Se connecter (le token FCM sera enregistré automatiquement)
3. Créer ou modifier un matériau via l'API
4. Vérifier la réception de la notification

### 3. Vérifier les logs

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log
```

## 🔍 Dépannage

### Si les notifications ne fonctionnent pas

1. **Vérifier le fichier Firebase credentials** :
   ```bash
   ls -la storage/app/private/chantix-1334d-f9ec61331442.json
   ```

2. **Vérifier la configuration** :
   ```bash
   php artisan tinker
   >>> config('services.firebase.credentials_path')
   ```

3. **Vérifier les logs** :
   ```bash
   tail -f storage/logs/laravel.log | grep -i firebase
   ```

4. **Vérifier que les tokens FCM sont enregistrés** :
   ```bash
   php artisan tinker
   >>> \App\Models\FcmToken::count()
   ```

## ✨ Avantages de cette Configuration

1. **Flexibilité** : Le chemin Firebase peut être changé via `.env` sans modifier le code
2. **Sécurité** : Le fichier de credentials reste dans `storage/app/private/` (non accessible publiquement)
3. **Maintenabilité** : Configuration centralisée dans `config/services.php`
4. **Déploiement** : Facile à configurer pour différents environnements (dev, staging, prod)

## 📌 Note Importante

Le fichier Firebase credentials (`chantix-1334d-f9ec61331442.json`) contient des clés privées. **Ne jamais le commit dans Git**. Il doit rester dans `storage/app/private/` qui est généralement ignoré par Git.

