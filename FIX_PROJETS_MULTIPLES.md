# Correction : Affichage et sauvegarde des projets multiples

## Problème identifié

1. **Dans la liste des invitations** : Seul un projet est affiché même si plusieurs projets ont été sélectionnés
2. **Dans le formulaire d'édition** : Les projets multiples sélectionnés ne sont pas récupérés/affichés

## Cause principale

La table `invitation_project` (table pivot pour la relation many-to-many entre invitations et projets) n'existe pas encore sur le serveur de production. Sans cette table, les projets multiples ne peuvent pas être sauvegardés ni récupérés.

## Solution appliquée

### 1. Corrections dans le contrôleur (`InvitationController.php`)

- ✅ **Méthode `edit()`** : Charge correctement la relation `projects` avant de récupérer les IDs
- ✅ **Méthode `update()`** : Charge correctement les anciens projets et sauvegarde tous les projets sélectionnés
- ✅ **Méthode `index()`** : Charge la relation `projects` si la table existe
- ✅ **Méthode `store()`** : Sauvegarde tous les projets sélectionnés dans la table pivot
- ✅ **Méthode `accept()`** : Associe tous les projets à l'utilisateur lors de l'acceptation

### 2. Corrections dans les vues

- ✅ **`index.blade.php`** : Affiche tous les projets associés à chaque invitation
- ✅ **`edit.blade.php`** : Utilise `$selectedProjectIds` pour pré-sélectionner tous les projets
- ✅ **`show.blade.php`** : Affiche tous les projets associés à l'invitation

### 3. Gestion défensive

Le code gère maintenant deux cas :
- **Si la table `invitation_project` existe** : Utilise la relation many-to-many pour gérer plusieurs projets
- **Si la table n'existe pas** : Utilise l'ancienne colonne `project_id` comme fallback (un seul projet)

## Action requise : Exécuter la migration

**IMPORTANT** : Pour que les projets multiples fonctionnent correctement, vous devez exécuter la migration sur le serveur de production :

```bash
cd /chemin/vers/votre/projet/chantix

# Exécuter la migration
php artisan migrate

# Ou spécifiquement cette migration
php artisan migrate --path=database/migrations/2025_12_07_003227_create_invitation_project_table.php
```

### Vérification après migration

1. **Vérifier que la table existe** :
```bash
php artisan tinker
>>> \Illuminate\Support\Facades\Schema::hasTable('invitation_project');
# Devrait retourner true
```

2. **Tester la création d'une invitation avec plusieurs projets** :
   - Créer une nouvelle invitation
   - Sélectionner plusieurs projets
   - Vérifier que tous les projets sont affichés dans la liste

3. **Tester la modification d'une invitation** :
   - Modifier une invitation existante
   - Vérifier que les projets précédemment sélectionnés sont bien pré-sélectionnés
   - Ajouter ou retirer des projets
   - Vérifier que tous les projets sont sauvegardés

## Migration des données existantes (optionnel)

Si vous avez des invitations existantes avec des projets associés via l'ancienne colonne `project_id`, vous pouvez les migrer vers la nouvelle table pivot :

```php
// Dans tinker ou une commande artisan
$invitations = \App\Models\Invitation::whereNotNull('project_id')->get();

foreach ($invitations as $invitation) {
    if ($invitation->project_id) {
        // Vérifier que la relation n'existe pas déjà
        if (!$invitation->projects()->where('project_id', $invitation->project_id)->exists()) {
            $invitation->projects()->attach($invitation->project_id);
        }
    }
}
```

## Résultat attendu

Après avoir exécuté la migration :

1. ✅ **Liste des invitations** : Tous les projets associés sont affichés (plusieurs badges si plusieurs projets)
2. ✅ **Formulaire d'édition** : Tous les projets sélectionnés sont pré-sélectionnés dans le formulaire
3. ✅ **Sauvegarde** : Tous les projets sélectionnés sont sauvegardés dans la table pivot
4. ✅ **Affichage détaillé** : Tous les projets sont affichés dans la page de détails

## Notes importantes

- Les modifications de code sont rétrocompatibles : si la table n'existe pas, le système utilise l'ancienne colonne `project_id`
- Une fois la migration exécutée, toutes les nouvelles invitations pourront avoir plusieurs projets
- Les invitations existantes avec `project_id` continueront de fonctionner, mais pour gérer plusieurs projets, il faudra les modifier via le formulaire d'édition



