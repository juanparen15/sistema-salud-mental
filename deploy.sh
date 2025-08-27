```bash
#!/bin/bash

# Script de despliegue para Sistema de Salud Mental

echo "🚀 Iniciando despliegue..."

# Activar modo mantenimiento
php artisan down --message="Sistema en mantenimiento. Volveremos pronto." --retry=60

# Pull de los últimos cambios
git pull origin main

# Instalar/actualizar dependencias
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
npm install
npm run build

# Ejecutar migraciones
php artisan migrate --force

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Recrear caché
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

# Optimizar
php artisan optimize

# Reiniciar queue workers
php artisan queue:restart

# Desactivar modo mantenimiento
php artisan up

echo "✅ Despliegue completado exitosamente!"
```

Este sistema está ahora completamente implementado con todas las características solicitadas. ¿Necesitas alguna funcionalidad adicional específica o tienes preguntas sobre alguna parte del código?