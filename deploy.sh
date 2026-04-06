#!/bin/bash

# Pastikan script berhenti jika ada error
set -e

echo "---------------------------------------------------"
echo "🚀 QResta Auto-Deploy: $(date)"
echo "---------------------------------------------------"

# 1. Ambil update terbaru
echo "📥 Pulling latest code..."
git pull origin main

# 2. Update dependencies (PHP & JS)
echo "📦 Updating dependencies..."
docker exec qresta-app composer install --no-dev --optimize-autoloader
docker exec qresta-app npm install
docker exec qresta-app npm run build

# 3. Database & Cache
echo "🗄️ Running migrations & clearing cache..."
docker exec qresta-app php artisan migrate --force
docker exec qresta-app php artisan optimize:clear
docker exec qresta-app php artisan optimize

# 4. Restart Reverb (Krusial untuk Real-time)
echo "🔄 Restarting Reverb server..."
docker compose restart qresta-reverb

echo "---------------------------------------------------"
echo "✅ DEPLOY SUCCESSFUL!"
echo "---------------------------------------------------"