#!/bin/bash

# Tester l'API directement pour voir ce qu'elle retourne

EMAIL="aminefaye@gmail.com"

echo "=========================================="
echo "TEST API PROJECTS pour: $EMAIL"
echo "=========================================="
echo ""

echo "1. Génération d'un token pour l'utilisateur..."
TOKEN=$(php artisan tinker --execute="
\$user = App\Models\User::where('email', '$EMAIL')->first();
if (\$user) {
    \$token = \$user->createToken('test-token')->plainTextToken;
    echo \$token;
}
")

if [ -z "$TOKEN" ]; then
    echo "❌ Impossible de générer un token"
    exit 1
fi

echo "Token généré: ${TOKEN:0:20}..."
echo ""

echo "2. Test de l'endpoint API /api/v1/projects..."
echo ""

# Récupérer l'URL de base depuis .env ou utiliser localhost
BASE_URL="http://localhost"
if [ -f .env ]; then
    APP_URL=$(grep "^APP_URL" .env | cut -d '=' -f2 | tr -d ' ' | tr -d '"')
    if [ ! -z "$APP_URL" ]; then
        BASE_URL="$APP_URL"
    fi
fi

echo "URL de base: $BASE_URL"
echo ""

# Faire la requête API
curl -s -X GET "$BASE_URL/api/v1/projects" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.' 2>/dev/null || curl -s -X GET "$BASE_URL/api/v1/projects" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $TOKEN"

echo ""
echo ""
echo "3. Vérification des logs de l'API..."
echo ""
tail -n 50 storage/logs/laravel.log | grep -A 10 "API Projects" | tail -n 30
