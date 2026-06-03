package main

import (
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
// ProcessRawLog expandido para detetar anomalias de Roteadores e Windows
func ProcessRawLog(rawMessage string, clientIP string) {
	fmt.Printf("📥 Log bruto recebido: %s\n", rawMessage)

	logData := SyslogData{
		IP:          clientIP,
		Mac:         "N/A",
		Workgroup:   "CÂMARA-MUNICIPAL",
		Severity:    "INFO",
		Workstation: "NETWORK-DEVICE",
		Host:        "UNKNOWN",
	}

	// 1. Extrair o Hostname / Identificador do dispositivo
	words := strings.Fields(rawMessage)
	if len(words) > 3 {
		logData.Host = words[3] // Ex: ROTEADOR-CENTRAL ou DESKTOP-GABS
		logData.Workstation = words[3]
	}

	// 2. IDENTIFICAÇÃO DE ANOMALIAS DE REDE E SEGURANÇA

	// Padrão A: Falhas de Autenticação (Windows Event 4625 ou Roteadores "LOGIN_FAILED" / "Authentication failed")
	authFailRegex := regexp.MustCompile(`(?i)(4625|LOGIN_FAILED|Authentication\s+failed|failed\s+login)`)

	// Padrão B: Tentativas de Invasão / Ataques (PortScan, BruteForce, DoS, packet drop na Firewall)
	attackRegex := regexp.MustCompile(`(?i)(PortScan|BruteForce|Attack|DoS|drop|deny|unauthorized)`)

	if authFailRegex.MatchString(rawMessage) {
		logData.ID = "AUTH_FAIL"
		logData.Severity = "CRITICAL"

		// Tenta capturar o utilizador que tentaram usar no ataque
		userRegex := regexp.MustCompile(`(?i)(?:user|utilizador)[:\s"']\s*([a-zA-Z0-9._-]+)`)
		if matches := userRegex.FindStringSubmatch(rawMessage); len(matches) > 1 {
			logData.User = matches[1]
		} else {
			logData.User = "UNKNOWN_TARGET"
		}
		logData.Msg = "🚨 ALERTA DE SEGURANÇA: Falha de Autenticação detetada."

	} else if attackRegex.MatchString(rawMessage) {
		logData.ID = "ATTACK_DETECTED"
		logData.Severity = "EMERGENCY" // Nível máximo para o Laravel disparar notificações
		logData.User = "FIREWALL"
		logData.Msg = "🛡️ Bloqueio Ativo: Tentativa de Intrusão/Anomalia na Firewall."

	} else {
		// Se o log passou pelos filtros do roteador mas não é crítico, categoriza como Info/Warning
		logData.ID = "SYS_WARN"
		logData.Severity = "WARNING"
		logData.User = "SYSTEM"
		logData.Msg = "Aviso do Sistema de Rede."
	}

	// Preserva o texto bruto original no campo final para auditoria profunda no Laravel
	logData.Msg = logData.Msg + " | Log Original: " + rawMessage

	// --- PERSISTÊNCIA COMPLETA AUTOMÁTICA ---
	saveToPostgres(logData)

	// Se for algo grave (CRITICAL ou EMERGENCY), manda direto pro painel via Redis/Laravel Worker
	if logData.Severity == "CRITICAL" || logData.Severity == "EMERGENCY" {
		err := sendToRedis(logData)
		if err != nil {
			fmt.Printf("❌ Erro ao enviar para Redis: %v\n", err)
			return
		}

		fmt.Println("✅ Log crítico/emergência processado e enviado para o painel com sucesso.")
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

func sendToRedis(data SyslogData) error {
	// Encaminha o fluxo diretamente para a função centralizada do redis.go
	// que trata do payload serializado em PHP
	pushToRedis(data)
	return nil
}
