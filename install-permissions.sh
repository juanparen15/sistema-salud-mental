// ================================
// SCRIPT DE IMPLEMENTACIÓN FINAL
// ================================

// install-permissions.sh
#!/bin/bash

echo "🏥 INSTALANDO SISTEMA DE PERMISOS - SALUD MENTAL"
echo "==============================================="

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para mostrar errores
error() {
    echo -e "${RED}❌ Error: $1${NC}"
    exit 1
}

# Función para mostrar éxito
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Función para mostrar advertencia
warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Función para mostrar información
info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Verificar requisitos
echo "🔍 Verificando requisitos..."

# Verificar PHP
php --version > /dev/null 2>&1 || error "PHP no está instalado"
success "PHP instalado"

# Verificar Composer
composer --version > /dev/null 2>&1 || error "Composer no está instalado"
success "Composer instalado"

# Verificar Laravel
[ -f "artisan" ] || error "Este no parece ser un proyecto Laravel"
success "Proyecto Laravel detectado"

# Paso 1: Limpiar cachés
echo "🧹 Limpiando cachés..."
php artisan cache:clear > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
success "Cachés limpiados"

# Paso 2: Instalar dependencia de permisos
echo "📦 Verificando dependencias..."
if ! composer show spatie/laravel-permission > /dev/null 2>&1; then
    info "Instalando spatie/laravel-permission..."
    composer require spatie/laravel-permission || error "No se pudo instalar spatie/laravel-permission"
    success "spatie/laravel-permission instalado"
else
    success "spatie/laravel-permission ya instalado"
fi

# Paso 3: Publicar migraciones de permisos
echo "📄 Publicando configuraciones de permisos..."
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force > /dev/null 2>&1
success "Configuraciones publicadas"

# Paso 4: Ejecutar migraciones
echo "🗄️  Ejecutando migraciones..."
php artisan migrate --force || error "Error ejecutando migraciones"
success "Migraciones ejecutadas"

# Paso 5: Ejecutar seeders de permisos
echo "🌱 Ejecutando seeders de permisos..."
php artisan db:seed --class=RolesAndPermissionsSeeder --force || error "Error ejecutando seeder de roles y permisos"
success "Roles y permisos creados"

# Paso 6: Verificar y corregir sistema
echo "🔧 Verificando y corrigiendo sistema de permisos..."
php artisan mental-health:fix-permissions || warning "Algunos problemas detectados, pero se corrigieron automáticamente"
success "Sistema de permisos verificado"

# Paso 7: Limpiar y optimizar
echo "⚡ Optimizando sistema..."
php artisan permission:cache-reset > /dev/null 2>&1
php artisan config:cache > /dev/null 2>&1
php artisan route:cache > /dev/null 2>&1
success "Sistema optimizado"

# Paso 8: Probar sistema
echo "🧪 Probando sistema de permisos..."
php artisan mental-health:test-permissions > /dev/null 2>&1 && success "Pruebas de permisos EXITOSAS" || warning "Algunas pruebas fallaron, revisar logs"

# Mostrar resumen final
echo ""
echo "🎉 ¡INSTALACIÓN COMPLETADA!"
echo "=========================="
echo ""
info "Credenciales por defecto creadas:"
echo ""
echo "👨‍💼 ADMIN:"
echo "   📧 Email: admin@saludmental.gov.co"
echo "   🔑 Contraseña: admin123"
echo "   🔐 Permisos: Gestión completa del sistema"
echo ""
echo "👨‍🏫 COORDINADOR:"
echo "   📧 Email: coordinador@saludmental.gov.co"
echo "   🔑 Contraseña: coord123"
echo "   🔐 Permisos: Supervisión de equipos y reportes"
echo ""
echo "👨‍⚕️ PSICÓLOGO:"
echo "   📧 Email: psicologo@saludmental.gov.co"
echo "   🔑 Contraseña: psico123"
echo "   🔐 Permisos: Atención especializada"
echo ""
echo "👨‍💼 TRABAJADOR SOCIAL:"
echo "   📧 Email: trabajador@saludmental.gov.co"
echo "   🔑 Contraseña: social123"
echo "   🔐 Permisos: Intervención social"
echo ""
echo "👨‍💻 AUXILIAR:"
echo "   📧 Email: auxiliar@saludmental.gov.co"
echo "   🔑 Contraseña: aux123"
echo "   🔐 Permisos: Registro básico"
echo ""
warning "⚠️  IMPORTANTE: Cambia estas contraseñas en producción"
echo ""
info "🌐 Acceder al sistema:"
echo "   php artisan serve"
echo "   Abrir: http://127.0.0.1:8000/admin"
echo ""
info "🛠️  Comandos útiles:"
echo "   php artisan mental-health:check-permissions    # Verificar permisos"
echo "   php artisan mental-health:test-permissions     # Probar sistema"
echo "   php artisan mental-health:fix-permissions      # Corregir problemas"
echo ""
success "¡Sistema de permisos listo para usar! 🚀"