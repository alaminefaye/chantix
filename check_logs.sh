#!/bin/bash

# Vérifier les logs pour voir ce qui se passe avec accessibleByUser

echo "=========================================="
echo "VÉRIFICATION DES LOGS accessibleByUser"
echo "=========================================="
echo ""

echo "Dernières 100 lignes contenant accessibleByUser:"
tail -n 200 storage/logs/laravel.log | grep -A 10 "accessibleByUser" | tail -n 50

echo ""
echo "=========================================="
echo "VÉRIFICATION DES LOGS API Projects"
echo "=========================================="
echo ""

echo "Dernières lignes contenant API Projects:"
tail -n 200 storage/logs/laravel.log | grep -A 5 "API Projects" | tail -n 30
