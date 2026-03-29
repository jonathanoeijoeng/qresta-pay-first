#!/bin/bash

# Ambil komentar dari argumen pertama, jika kosong gunakan default "Update QResta"
COMMENT=${1:-"Update QResta: Auto-commit"}

echo "🚀 Memulai proses sinkronisasi Git..."

# 1. Menambahkan semua perubahan
git add .

# 2. Commit dengan pesan
git commit -m "$COMMENT"

# 3. Push ke repository (asumsi branch utama adalah main)
git push origin main

echo "✅ Berhasil dipush dengan komentar: '$COMMENT'"
