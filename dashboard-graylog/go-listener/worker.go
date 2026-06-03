package main

import (
	"context"
	"encoding/json"
	"fmt"
	"regexp"
	"strings"
	"time"
)

// SyslogData é o teu DTO estruturado idêntico ao que o Laravel espera
type SyslogData struct {
	ID          string `json:"id"`
	IP          string `json:"ip"`
	Mac         string `json:"mac"`
	Host        string `json:"host"`
	Workstation string `json:"workstation"`
	Workgroup   string `json:"workgroup"`
	Severity    string `json:"severity"`
	User        string `json:"user"`
	Msg         string `json:"msg"`
}

// LaravelJobPayload representa a estrutura exata que o queue:work do Laravel exige para ler a fila do Redis
type LaravelJobPayload struct {
	DisplayName string `json:"displayName"`
	Job         string `json:"job"`
	MaxTries    *int   `json:"maxTries"`
	Delay       *int   `json:"delay"`
	Timeout     *int   `json:"timeout"`
	Data        struct {
		LogData SyslogData `json:"logData"`
	} `json:"data"`
}

// ProcessRawLog recebe a string bruta do Windows e normaliza os dados
func ProcessRawLog(rawMessage string, clientIP string) {
	fmt.Printf("📥 Log bruto recebido: %s\n", rawMessage)

	// 1. Criar uma instância limpa do nosso DTO com valores padrão
	logData := SyslogData{
		IP:          clientIP,
		Mac:         "N/A",
		Workgroup:   "WORKGROUP",
		Severity:    "INFO", // Padrão
		Workstation: "UNKNOWN",
		Host:        "UNKNOWN",
	}

	// 2. Extrair o Event ID do Windows (ex: procura por "4625" ou "4624")
	idRegex := regexp.MustCompile(`\b(4625|4624|4720|4634)\b`)
	if match := idRegex.FindString(rawMessage); match != "" {
		logData.ID = match
		if match == "4625" {
			logData.Severity = "CRITICAL" // Falha de autenticação é crítico!
		}
	} else {
		logData.ID = "0" // ID genérico se não identificar
	}

	// 3. Extrair o Hostname
	words := strings.Fields(rawMessage)
	if len(words) > 3 {
		logData.Host = words[3]
		logData.Workstation = words[3]
	}

	// 4. Extrair o Utilizador envolvido
	userRegex := regexp.MustCompile(`(?i)(?:user|utilizador)[:\s]+([a-zA-Z0-9._-]+)`)
	if matches := userRegex.FindStringSubmatch(rawMessage); len(matches) > 1 {
		logData.User = matches[1]
	} else {
		logData.User = "SYSTEM" // Fallback
	}

	// 5. A mensagem completa passa a ser o texto limpo
	logData.Msg = rawMessage

	// --- FIM DA NORMALIZAÇÃO ---

	// 6. Gravar no PostgreSQL na tabela `syslogs` mapeada com as colunas certas do Laravel
	err := saveToPostgres(logData)
	if err != nil {
		fmt.Printf("❌ Erro ao salvar no Postgres: %v\n", err)
		return
	}
	fmt.Println("✅ Log normalizado e guardado no PostgreSQL!")

	// 7. Se for Crítico (Event 4625), injetar no Redis no formato que o Laravel Queue compreende
	if logData.Severity == "CRITICAL" {
		err := pushToRedis(logData)
		if err != nil {
			fmt.Printf("❌ Erro ao enviar para o Redis: %v\n", err)
			return
		}
		fmt.Println("🚀 Alerta crítico injetado no Redis para o painel Laravel!")
	}
}

func saveToPostgres(data SyslogData) error {
	// Query ajustada para coincidir com a estrutura da migração do teu painel Filament
	// Insere os campos normais e preenche os timestamps criados pelo Laravel (created_at/updated_at)
	query := `INSERT INTO syslogs (id_evento, ip_origem, mac_origem, hostname, workstation, workgroup, severity, username, mensagem, created_at, updated_at) 
	          VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)`

	now := time.Now()
	_, err := db.Exec(query, data.ID, data.IP, data.Mac, data.Host, data.Workstation, data.Workgroup, data.Severity, data.User, data.Msg, now, now)
	return err
}

func pushToRedis(data SyslogData) error {
	ctx := context.Background()

	// ⚠️ ATENÇÃO: O Laravel não aceita um JSON simples solto no Redis se usares filas (Queue)
	// Ele exige um envelope estruturado indicando qual Job do PHP deve processar o dado.
	payload := LaravelJobPayload{
		DisplayName: "App\\Jobs\\ProcessarAlertaCritico",
		Job:         "App\\Jobs\\ProcessarAlertaCritico",
		MaxTries:    nil,
		Delay:       nil,
		Timeout:     nil,
	}
	payload.Data.LogData = data

	jsonData, err := json.Marshal(payload)
	if err != nil {
		return err
	}

	// Injeta na chave exata que o teu "QUEUE_CONNECTION=redis" do Laravel escuta no .env
	return rdb.RPush(ctx, "queues:default", jsonData).Err()
}
