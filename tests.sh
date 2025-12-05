#!/bin/bash

echo "==================================="
echo "Pruebas API - Lanería Mariano Díaz"
echo "==================================="

BASE_URL="http://127.0.0.1:8000/api/v1"

echo ""
echo "1. Probando Login..."
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@laneriamariano.com",
    "password": "admin123"
  }')

echo "$LOGIN_RESPONSE" | jq '.'

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')

if [ "$TOKEN" != "null" ]; then
  echo "✅ Login exitoso"
  echo "Token: $TOKEN"
else
  echo "❌ Error en login"
  exit 1
fi

echo ""
echo "2. Probando endpoint de productos públicos..."
curl -s "$BASE_URL/productos" | jq '.data | length'

echo ""
echo "3. Probando endpoint protegido (Dashboard)..."
curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/reportes/dashboard" | jq '.data.ventas'

echo ""
echo "4. Probando alertas de stock..."
curl -s -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/inventario/alertas/stock-bajo" | jq '.data.total_alertas'

echo ""
echo "==================================="
echo "✅ Pruebas completadas"
echo "==================================="