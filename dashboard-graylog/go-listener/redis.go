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
	data, err := json.Marshal(dto)
	if err != nil {
		fmt.Printf("❌ Erro ao parsear DTO para Redis: %v\n", err)
		return
	}
	err = rdb.Publish(ctxRedis, "syslogs_channel", data).Err()
	if err != nil {
		fmt.Printf("❌ Erro ao publicar no Redis: %v\n", err)
	}
}
