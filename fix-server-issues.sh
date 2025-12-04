#!/bin/bash

# Script pour corriger les problèmes sur le serveur

echo "🔧 Correction des problèmes du serveur..."
echo ""

cd "$(dirname "$0")" || exit 1

# 1. Corriger les rôles dupliqués
echo "📋 Correction des rôles dupliqués..."
php artisan tinker --execute="
    // Supprimer les rôles en double (garder les premiers)
    \$rolesToKeep = ['admin', 'chef_chantier', 'ingenieur', 'ouvrier', 'comptable', 'superviseur'];
    foreach (\$rolesToKeep as \$roleName) {
        \$roles = \App\Models\Role::where('name', \$roleName)->orderBy('id')->get();
        if (\$roles->count() > 1) {
            // Garder le premier, supprimer les autres
            \$firstRole = \$roles->first();
            \$duplicates = \$roles->skip(1);
            echo 'Rôle ' . \$roleName . ': garder ID ' . \$firstRole->id . ', supprimer ' . \$duplicates->count() . ' doublon(s)' . PHP_EOL;
            
            // Mettre à jour les références dans company_user
            foreach (\$duplicates as \$duplicate) {
                \DB::table('company_user')
                    ->where('role_id', \$duplicate->id)
                    ->update(['role_id' => \$firstRole->id]);
                
                // Supprimer le doublon
                \$duplicate->delete();
            }
        }
    }
    echo '✅ Rôles corrigés' . PHP_EOL;
" || echo "⚠️  Erreur lors de la correction des rôles"
echo ""

# 2. Vérifier et corriger l'utilisateur
echo "👤 Vérification de l'utilisateur..."
read -p "Email de l'utilisateur à vérifier: " user_email

if [ ! -z "$user_email" ]; then
    php artisan tinker --execute="
        \$user = \App\Models\User::where('email', '$user_email')->first();
        if (\$user) {
            echo 'Utilisateur trouvé: ' . \$user->name . ' (ID: ' . \$user->id . ')' . PHP_EOL;
            echo 'Super Admin: ' . (\$user->is_super_admin ? 'Oui' : 'Non') . PHP_EOL;
            echo 'Current Company ID: ' . (\$user->current_company_id ?? 'Aucun') . PHP_EOL;
            echo PHP_EOL;
            echo 'Relations company_user:' . PHP_EOL;
            \$companies = \$user->companies()->get();
            foreach (\$companies as \$company) {
                \$pivot = \$user->companies()->where('companies.id', \$company->id)->first()->pivot;
                \$role = \App\Models\Role::find(\$pivot->role_id);
                echo '  - ' . \$company->name . ' (ID: ' . \$company->id . ')' . PHP_EOL;
                echo '    Rôle: ' . (\$role ? \$role->name . ' (ID: ' . \$role->id . ')' : 'Aucun') . PHP_EOL;
                echo '    Is Active: ' . (\$pivot->is_active ? 'Oui' : 'Non') . PHP_EOL;
                
                // Vérifier si le rôle est admin
                if (\$role && \$role->name === 'admin') {
                    echo '    ✅ Rôle admin correct' . PHP_EOL;
                } else {
                    echo '    ⚠️  Rôle admin manquant ou incorrect' . PHP_EOL;
                    // Corriger automatiquement
                    \$adminRole = \App\Models\Role::where('name', 'admin')->first();
                    if (\$adminRole) {
                        \DB::table('company_user')
                            ->where('user_id', \$user->id)
                            ->where('company_id', \$company->id)
                            ->update(['role_id' => \$adminRole->id]);
                        echo '    ✅ Rôle admin corrigé automatiquement' . PHP_EOL;
                    }
                }
                echo PHP_EOL;
            }
        } else {
            echo '❌ Utilisateur non trouvé' . PHP_EOL;
        }
    " || echo "⚠️  Erreur lors de la vérification de l'utilisateur"
fi
echo ""

# 3. Vérifier les invitations
echo "📧 Vérification des invitations..."
php artisan tinker --execute="
    \$invitations = \App\Models\Invitation::with('inviter')->get();
    echo 'Total invitations: ' . \$invitations->count() . PHP_EOL;
    foreach (\$invitations as \$invitation) {
        echo PHP_EOL;
        echo 'Invitation ID: ' . \$invitation->id . PHP_EOL;
        echo '  Email: ' . \$invitation->email . PHP_EOL;
        echo '  Company ID: ' . \$invitation->company_id . PHP_EOL;
        echo '  Invited By: ' . (\$invitation->invited_by ?? 'NULL') . PHP_EOL;
        if (\$invitation->inviter) {
            echo '  Créateur: ' . \$invitation->inviter->name . ' (ID: ' . \$invitation->inviter->id . ')' . PHP_EOL;
        } else {
            echo '  ⚠️  Créateur non trouvé (invited_by: ' . \$invitation->invited_by . ')' . PHP_EOL;
        }
    }
" || echo "⚠️  Erreur lors de la vérification des invitations"
echo ""

# 4. Vider les caches
echo "🧹 Vidage des caches..."
php artisan optimize:clear
echo "✅ Caches vidés"
echo ""

echo "✅ Corrections terminées!"

