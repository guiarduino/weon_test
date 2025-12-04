#!/bin/bash
set -e

# Espera até o banco estar disponível
echo "Aguardando banco de dados..."
while ! php -r "new PDO('mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}','${DB_USERNAME}','${DB_PASSWORD}');" >/dev/null 2>&1; do
  echo "Banco ainda não disponível, tentando novamente em 2 segundos..."
  sleep 2
done
echo "Banco disponível!"

# Roda as migrations do Laravel
php artisan migrate --force

# Mantém o Apache rodando
apache2-foreground