#!/bin/bash

echo "======================================"
echo "   🔒 TESTING SEGURO - LANERÍA MD"
echo "======================================"
echo ""

# Verificar que estamos en entorno correcto
echo "🔍 Verificando entorno de testing..."
php artisan tinker --env=testing <<EOF
if (DB::connection()->getDatabaseName() !== 'laneria_mariano_test') {
    echo "⚠️  ERROR: No estás en la BD de testing!\n";
    exit(1);
}
echo "✅ BD de testing correcta: " . DB::connection()->getDatabaseName() . "\n";
exit(0);
EOF

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ DETENIDO: Verifica tu configuración antes de continuar"
    exit 1
fi

echo ""
echo "🗄️  Recreando BD de testing..."
php artisan migrate:fresh --env=testing --seed

echo ""
echo "🧪 Ejecutando tests..."
php artisan test

echo ""
echo "======================================"
echo "         ✅ TESTS COMPLETADOS"
echo "======================================"