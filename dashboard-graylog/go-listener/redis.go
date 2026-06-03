package main

import (
	"context"
	"encoding/json"
	"fmt"
	"os"

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

func publishToRedis(dto SyslogDTO) {
	// Só enviamos para o Redis se for um alerta de segurança crítico
	if dto.Severity != "CRITICAL" && dto.EventID != 4625 {
		return
	}

	// Estrutura payload exatamente igual ao formato de serialização de Jobs do Laravel
	laravelPayload := map[string]interface{}{
		"uuid":          "", // Pode ir vazio, o Laravel gera se necessário
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
				"O:32:\"App\\Jobs\\ProcessarAlertaCritico\":1:{s:8:\"\x00*\x00dados\";a:6:{s:8:\"event_id\";i:%d;s:8:\"username\";s:%d:\"%s\";s:10:\"ip_address\";s:%d:\"%s\";s:11:\"mac_address\";s:%d:\"%s\";s:8:\"hostname\";s:%d:\"%s\";s:11:\"received_at\";s:%d:\"%s\";}}",
				dto.EventID,
				len(dto.Username), dto.Username,
				len(dto.IPAddress), dto.IPAddress,
				len(dto.MacAddress), dto.MacAddress,
				len(dto.Hostname), dto.Hostname,
				len(dto.ReceivedAt.Format("2006-01-02T15:04:05Z07:00")), dto.ReceivedAt.Format("2006-01-02T15:04:05Z07:00"),
			),
		},
	}

	data, err := json.Marshal(laravelPayload)
	if err != nil {
		fmt.Printf("❌ Erro ao estruturar Payload Laravel para Redis: %v\n", err)
		return
	}

	// Injeta diretamente na Lista (Fila) que o comando "php artisan queue:work" escuta
	err = rdb.RPush(ctxRedis, "queues:default", data).Err()
	if err != nil {
		fmt.Printf("❌ Erro ao inserir na fila do Redis: %v\n", err)
	} else {
		fmt.Println("🚀 Alerta crítico injetado diretamente na Queue do Redis.")
	}
}
