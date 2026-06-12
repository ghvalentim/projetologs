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

if [ -f compose.yml ]; then
    echo "✓ compose.yml encontrado. Iniciando containers..."
    if ! command -v docker &> /dev/null; then
        echo "❌ Erro: Docker não está instalado ou não está no PATH. Por favor, instale o Docker para continuar."
        exit 1
    else 
        echo "✓ Docker encontrado. Continuando com a inicialização dos containers."
        if ! command -v docker compose &> /dev/null; then
            echo "❌ Erro: Docker Compose não está disponível. Certifique-se de que você tem uma versão do Docker que inclui o Docker Compose."
            exit 1
        else
            echo "✓ Docker Compose encontrado. Continuando com a inicialização dos containers."
            #se docker já estiver rodando, fazer docker compose down para evitar conflitos
            if docker compose ps -q; then
                echo "⚠️ Containers Docker já estão rodando. Parando containers existentes para evitar conflitos..."
                docker compose down
                echo "✓ Containers parados com sucesso. Continuando com a inicialização."
            else
                echo "✓ Nenhum container Docker em execução. Continuando com a inicialização."
            fi
        fi
    fi
else
    echo "❌ Erro: compose.yml não foi encontrado! Certifique-se de estar no diretório correto."
    exit 1
fi

echo -e "\n=== Instalando dependências (Composer) ==="
docker compose run --rm --no-deps laravel-app composer install --no-interaction --prefer-dist --optimize-autoloader
echo "✓ Dependências do Composer instaladas com sucesso."
echo "\n=== Iniciando containers Docker ==="
docker compose up -d --build # Garantir que o container continue rodando após a instalação do composer

echo -e "\n=== Configurando o Laravel ==="
docker compose exec laravel-app php artisan key:generate

# Uma pequena pausa para garantir que o banco de dados já aceita conexões antes da migração
echo "Aguardando inicialização do banco de dados..."
sleep 3 

docker compose exec laravel-app php artisan migrate --force

echo -e "\n🚀 Ambiente configurado e rodando com sucesso!"