package main

import (
	"context"
	"crypto/rand"
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
		DB:   0, // 👈 DB 0 FIXO! É aqui que o config do Laravel lê as filas
	})

	if err := rdb.Ping(ctxRedis).Err(); err != nil {
		fmt.Printf("❌ Erro ao conectar no Redis: %v\n", err)
		return
	}
	fmt.Println("✅ Redis conectado com sucesso na DB 0")
}

// Gera um UUID válido para o padrão que o Laravel v11 exige nas tabelas/filas
func generateUUID() string {
	b := make([]byte, 16)
	_, _ = rand.Read(b) // Ignora os retornos para manter o helper simples
	return fmt.Sprintf("%x-%x-%x-%x-%x", b[0:4], b[4:6], b[6:8], b[8:10], b[10:])
}

// Injeta os alertas críticos diretamente no formato nativo esperado pelo Laravel v11
func pushToRedis(dto SyslogData) {
	// 1. FILTRO DE PERFORMANCE: Se não for crítico ou emergência, descarta imediatamente
	if dto.Severity != "CRITICAL" && dto.Severity != "EMERGENCY" {
		return
	}

	// 2. CAPTURA DOS TIMESTAMPS E UUID (Agora declarados ANTES de serem usados!)
	nowStr := time.Now().Format("2006-01-02T15:04:05Z07:00")
	eventIDStr := fmt.Sprintf("%d", dto.ID)
	jobUUID := generateUUID()

	// 3. SERIALIZAÇÃO PHP: Chaves curtas unificadas com o teu Laravel
	phpArrayData := fmt.Sprintf(
		"a:7:{s:2:\"id\";s:%d:\"%s\";s:4:\"user\";s:%d:\"%s\";s:2:\"ip\";s:%d:\"%s\";s:3:\"mac\";s:%d:\"%s\";s:4:\"host\";s:%d:\"%s\";s:8:\"severity\";s:%d:\"%s\";s:12:\"received_at\";s:%d:\"%s\";}",
		len(eventIDStr), eventIDStr,
		len(dto.User), dto.User,
		len(dto.IP), dto.IP,
		len(dto.Mac), dto.Mac,
		len(dto.Host), dto.Host,
		len(dto.Severity), dto.Severity,
		len(nowStr), nowStr,
	)

	// O:31 fixo porque "App\Jobs\ProcessarAlertaCritico" tem 31 caracteres
	phpCommandPayload := fmt.Sprintf(
		"O:31:\"App\\Jobs\\ProcessarAlertaCritico\":1:{s:7:\"logData\";%s}",
		phpArrayData,
	)

	// 4. ESTRUTURA DO PAYLOAD ORIGINAL DO LARAVEL V11
	laravelPayload := map[string]interface{}{
		"uuid":          jobUUID,
		"displayName":   "App\\Jobs\\ProcessarAlertaCritico",
		"job":           "Illuminate\\Queue\\CallQueuedHandler@call",
		"maxTries":      nil,
		"maxExceptions": nil,
		"failOnTimeout": false,
		"backoff":       nil,
		"timeout":       nil,
		"data": map[string]interface{}{
			"commandName": "App\\Jobs\\ProcessarAlertaCritico",
			"command":     phpCommandPayload,
		},
	}

	// 5. PARSE PARA JSON
	data, err := json.Marshal(laravelPayload)
	if err != nil {
		fmt.Printf("❌ Erro ao estruturar Payload Laravel para Redis: %v\n", err)
		return
	}

	// 6. DISPARO PARA A DB 0 DO REDIS (Chave com o prefixo do teu database.php)
	err = rdb.RPush(ctxRedis, "laravel-database-queues:default", data).Err()
	if err != nil {
		fmt.Printf("❌ Erro ao inserir na fila do Redis: %v\n", err)
	} else {
		fmt.Printf("🚀 [Redis Queue] Alerta nível %s enviado para a DB 0.\n", dto.Severity)
	}
}
