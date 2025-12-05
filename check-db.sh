#!/bin/bash

echo "======================================"
echo "   VERIFICACIÓN DE BASES DE DATOS"
echo "======================================"
echo ""

echo "📊 Base de datos PRINCIPAL (desarrollo):"
php artisan tinker --env=local <<EOF
echo "BD: " . DB::connection()->getDatabaseName() . "\n";
echo "Productos: " . DB::table('productos')->count() . "\n";
echo "Clientes: " . DB::table('clientes')->count() . "\n";
exit(0);
EOF

echo ""
echo "🧪 Base de datos de TESTING:"
php artisan tinker --env=testing <<EOF
echo "BD: " . DB::connection()->getDatabaseName() . "\n";
echo "Productos: " . DB::table('productos')->count() . "\n";
exit(0);
EOF

echo ""
echo "======================================"