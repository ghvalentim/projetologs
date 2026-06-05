package main

import (
	"fmt"
	"regexp"
	"strings"
)

// SyslogData mapeia exatamente a estrutura de dados que transita no sistema
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

// ProcessRawLog recebe a string bruta e normaliza os dados direcionando para o banco real
func ProcessRawLog(rawMessage string, clientIP string) {
	fmt.Printf("📥 Log bruto recebido: %s\n", rawMessage)

	logData := SyslogData{
		IP:          clientIP,
		Mac:         "N/A",
		Workgroup:   "ESTGOH-NETWORK",
		Severity:    "INFO",
		Workstation: "NETWORK-DEVICE",
		Host:        "UNKNOWN",
	}

	// 1. Extrair o Hostname / Identificador do dispositivo
	words := strings.Fields(rawMessage)
	if len(words) > 3 {
		logData.Host = words[3]
		logData.Workstation = words[3]
	}

	// 2. IDENTIFICAÇÃO DE ANOMALIAS DE REDE E SEGURANÇA
	authFailRegex := regexp.MustCompile(`(?i)(4625|LOGIN_FAILED|Authentication\s+failed|failed\s+login)`)
	attackRegex := regexp.MustCompile(`(?i)(PortScan|BruteForce|Attack|DoS|drop|deny|unauthorized)`)

	if authFailRegex.MatchString(rawMessage) {
		logData.ID = "4625"
		logData.Severity = "CRITICAL"

		userRegex := regexp.MustCompile(`(?i)(?:user|utilizador)[:\s"']\s*([a-zA-Z0-9._-]+)`)
		if matches := userRegex.FindStringSubmatch(rawMessage); len(matches) > 1 {
			logData.User = matches[1]
		} else {
			logData.User = "UNKNOWN_TARGET"
		}
		logData.Msg = "🚨 ALERTA DE SEGURANÇA: Falha de Autenticação detetada."

	} else if attackRegex.MatchString(rawMessage) {
		logData.ID = "9999"
		logData.Severity = "EMERGENCY"
		logData.User = "FIREWALL"
		logData.Msg = "🛡️ Bloqueio Ativo: Tentativa de Intrusão/Anomalia na Firewall."

	} else {
		logData.ID = "0"
		logData.Severity = "WARNING"
		logData.User = "SYSTEM"
		logData.Msg = "Aviso do Sistema de Rede."
	}

	// Preserva o texto bruto original no campo final para auditoria profunda no Laravel
	logData.Msg = logData.Msg + " | Log Original: " + rawMessage

	// --- ⚠️ CORREÇÃO DA PERSISTÊNCIA: Chama a função certa do database.go ---
	err := insertSyslog(logData)
	if err != nil {
		fmt.Printf("❌ [POSTGRES ERROR] Erro real ao inserir na base de dados: %v\n", err)
	} else {
		fmt.Println("✅ [POSTGRES SUCCESS] Log gravado na base de dados central do Laravel!")
	}

	// Se for algo grave (CRITICAL ou EMERGENCY), manda direto pro painel via Redis
	if logData.Severity == "CRITICAL" || logData.Severity == "EMERGENCY" {
		// Chama diretamente a função do redis.go que já corrigimos com o prefixo laravel-database-
		pushToRedis(logData)
		fmt.Println("✅ Processamento de fluxo crítico concluído.")
	}
}
