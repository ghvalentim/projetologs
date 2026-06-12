#!/bin/bash

set -e

echo "=== Checando .env ==="
if [ -f laravel-panel/.env ]; then
    echo "✓ .env já existe. Avançando."
else
    if [ -f laravel-panel/.env.example ]; then
        echo "➜ Criando .env a partir de .env.example..."
        cp laravel-panel/.env.example laravel-panel/.env
        echo "✓ Ficheiro .env criado com sucesso."
    else
        echo "❌ Erro: .env.example não foi encontrado! Não é possível criar o .env."
        exit 1
    fi
fi

echo -e "\n=== Iniciando containers Docker ==="
docker compose up -d --build

echo -e "\n=== Instalando dependências (Composer) ==="
# Usando o container em execução para rodar o composer
docker compose run --rm laravel-app composer install --no-interaction --prefer-dist --optimize-autoloader

docker compose up -d # Garantir que o container continue rodando após a instalação do composer

echo -e "\n=== Configurando o Laravel ==="
docker compose exec laravel-app php artisan key:generate

# Uma pequena pausa para garantir que o banco de dados já aceita conexões antes da migração
echo "Aguardando inicialização do banco de dados..."
sleep 3 

docker compose exec laravel-app php artisan migrate --force

echo -e "\n🚀 Ambiente configurado e rodando com sucesso!"