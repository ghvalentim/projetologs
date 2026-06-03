package main

import (
	"context"
	"encoding/json"
	"fmt"
	"os"
	"time"

	"github.com/redis/go-redis/v9"
)

var (
	rdb      *redis.Client
	ctxRedis = context.Background()
)

// Helper para ler variáveis de ambiente com fallback
func getEnv(key, fallback string) string {
	if value, exists := os.LookupEnv(key); exists {
		return value
	}
	return fallback
}

func initRedis() {
	host := getEnv("REDIS_HOST", "127.0.0.1")
	port := getEnv("REDIS_PORT", "6379")

	rdb = redis.NewClient(&redis.Options{
		Addr: fmt.Sprintf("%s:%s", host, port),
		DB:   0,
	})

	// Testa a conexão no início para evitar falhas silenciosas
	if err := rdb.Ping(ctxRedis).Err(); err != nil {
		fmt.Printf("❌ Erro ao conectar no Redis: %v\n", err)
		return
	}
	fmt.Println("✅ Redis conectado com sucesso")
}

// pushToRedis adaptado para receber o SyslogData vindo do processamento por Regex
func pushToRedis(dto SyslogData) {
	// Filtro dinâmico: Só enviamos para o Redis se for falha do Windows ou ataque em roteador
	if dto.Severity != "CRITICAL" && dto.Severity != "EMERGENCY" {
		return
	}

	nowStr := time.Now().Format("2006-01-02T15:04:05Z07:00")

	// Estrutura o payload injetando os dados normalizados na tua string de serialização do PHP
	laravelPayload := map[string]interface{}{
		"uuid":          "",
		"displayName":   "App\\Jobs\\ProcessarAlertaCritico",
		"job":           "Illuminate\\Queue\\CallQueuedHandler@call",
		"maxTries":      nil,
		"maxExceptions": nil,
		"failOnTimeout": false,
		"backoff":       nil,
		"timeout":       nil,
		"data": map[string]interface{}{
			"commandName": "App\\Jobs\\ProcessarAlertaCritico",
			"command": fmt.Sprintf(
				"O:32:\"App\\Jobs\\ProcessarAlertaCritico\":1:{s:8:\"\x00*\x00dados\";a:6:{s:8:\"event_id\";s:%d:\"%s\";s:8:\"username\";s:%d:\"%s\";s:10:\"ip_address\";s:%d:\"%s\";s:11:\"mac_address\";s:%d:\"%s\";s:8:\"hostname\";s:%d:\"%s\";s:11:\"received_at\";s:%d:\"%s\";}}",
				len(dto.ID), dto.ID,
				len(dto.User), dto.User,
				len(dto.IP), dto.IP,
				len(dto.Mac), dto.Mac,
				len(dto.Host), dto.Host,
				len(nowStr), nowStr,
			),
		},
	}

	data, err := json.Marshal(laravelPayload)
	if err != nil {
		fmt.Printf("❌ Erro ao estruturar Payload Laravel para Redis: %v\n", err)
		return
	}

	// Injeta diretamente na chave com o prefixo padrão que o Laravel Database Configuration usa
	err = rdb.RPush(ctxRedis, "laravel_database_queues:default", data).Err()
	if err != nil {
		fmt.Printf("❌ Erro ao inserir na fila do Redis: %v\n", err)
	} else {
		fmt.Println("🚀 Alerta crítico/emergência injetado diretamente na Queue do Redis.")
	}
}
