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
	Country     string `json:"country"`
	City        string `json:"city"`
	Latitude    string `json:"latitude"`
	Longitude   string `json:"longitude"`
}

// ProcessRawLog recebe a string bruta e normaliza os dados direcionando para o banco real
func ProcessRawLog(rawMessage string, clientIP string) {
	fmt.Printf("📥 Log bruto recebido: %s\n", rawMessage)

	// Inicialização base
	logData := SyslogData{
		IP:          clientIP,
		Mac:         "N/A",
		Workgroup:   "ESTGOH-NETWORK",
		Severity:    "INFO",
		Workstation: "NETWORK-DEVICE",
		Host:        "UNKNOWN",
	}

	// Tentar extrair o Hostname padrão do formato Syslog BSD
	words := strings.Fields(rawMessage)
	if len(words) > 3 {
		logData.Host = words[3]
		logData.Workstation = words[3]
	}

	// Localização Geográfica (A função que criaste anteriormente)
	country, city, lat, lon := geolocateIP(clientIP)
	logData.Country = country
	logData.City = city
	logData.Latitude = lat
	logData.Longitude = lon

	// 1. DICIONÁRIO DE REGEX TURBINADO
	ruidoDockerRegex := regexp.MustCompile(`(?i)(docker|containerd|veth|NetworkManager|wsl)`)
	authFailRegex := regexp.MustCompile(`(?i)(4625|LOGIN_FAILED|Authentication\s+failed|failed\s+login)`)
	successRegex := regexp.MustCompile(`(?i)(4624|LOGIN_SUCCESS|Accepted|session\s+opened)`)
	attackRegex := regexp.MustCompile(`(?i)(PortScan|BruteForce|Attack|\bDoS\b|\bdrop\b|\bdeny\b)`)
	
	rdpRegex := regexp.MustCompile(`(?i)(1149|TerminalServices|Logon Type:\s*10)`)
	sshRegex := regexp.MustCompile(`(?i)(OpenSSH|sshd)`)
	privilegeRegex := regexp.MustCompile(`(?i)(4648|4672|4673|Explicit credentials|Privileged Service|Special privileges|privilégios especiais)`)
	psRegex := regexp.MustCompile(`(?i)(4104|PowerShell)`)
	auditRegex := regexp.MustCompile(`(?i)(4720|4722|4732|4799|Security-Auditing|enumerada|adesão ao grupo)`)

	// Identificar vetor de entrada
	vetorAcesso := ""
	if rdpRegex.MatchString(rawMessage) {
		vetorAcesso = " via RDP (Remote Desktop)"
	} else if sshRegex.MatchString(rawMessage) {
		vetorAcesso = " via terminal SSH"
	}

	// 2. CLASSIFICAÇÃO DE EVENTOS E ATRIBUIÇÃO DE IDS FORMAIS
	
	if ruidoDockerRegex.MatchString(rawMessage) {
		logData.ID = "SYS_DOCKER" // Ruído do sistema interno
		logData.Severity = "AUDIT"
		logData.User = "SYSTEM"
		logData.Msg = "🐳 Auditoria de Sistema: Processo interno de rede/container."

	} else if authFailRegex.MatchString(rawMessage) {
		logData.ID = "4625" // Event ID real do Windows
		logData.Severity = "CRITICAL"

		userRegex := regexp.MustCompile(`(?i)(?:user|utilizador|Account Name|Nome da Conta)[:\s"']*\s*([a-zA-Z0-9._-]+)`)
		if matches := userRegex.FindStringSubmatch(rawMessage); len(matches) > 1 {
			logData.User = matches[1]
		} else {
			logData.User = "UNKNOWN_TARGET"
		}
		logData.Msg = "🚨 Falha de Autenticação" + vetorAcesso + " detetada."

	} else if privilegeRegex.MatchString(rawMessage) {
		// Distinguir rotina do sistema de escalonamento humano
		if regexp.MustCompile(`(?i)Nome da Conta:\s*SYSTEM`).MatchString(rawMessage) {
			logData.ID = "SYS_PRIV_AUTO"
			logData.Severity = "INFO"
			logData.User = "SYSTEM"
			logData.Msg = "ℹ️ Atribuição de privilégios de rotina (Processo do Sistema)."
		} else {
			logData.ID = "PRIV_ESCALATION" // Etiqueta formal de SOC
			logData.Severity = "EMERGENCY"
			
			contaRegex := regexp.MustCompile(`(?i)Nome da Conta:\s*([a-zA-Z0-9_$-]+)`)
			matches := contaRegex.FindAllStringSubmatch(rawMessage, -1)
			if len(matches) > 1 {
				logData.User = matches[1][1]
			}
			logData.Msg = "⚠️ Abuso de Privilégio: Tentativa humana de usar permissões reservadas."
		}

	} else if psRegex.MatchString(rawMessage) {
		logData.ID = "4104" // Event ID real de execução de PowerShell
		logData.Severity = "WARNING"
		logData.User = "UNKNOWN"
		logData.Msg = "💻 Atividade Suspeita: Execução de Script no PowerShell."

	} else if attackRegex.MatchString(rawMessage) {
		logData.ID = "NET_INTRUSION" // Etiqueta formal para anomalias de rede/firewall
		logData.Severity = "EMERGENCY"
		logData.User = "FIREWALL"
		logData.Msg = "🛡️ Anomalia de Rede/Firewall: Possível tentativa de intrusão detetada."

	} else if successRegex.MatchString(rawMessage) {
		logData.ID = "4624" // Event ID real do Windows
		logData.Severity = "SUCCESS"
		logData.User = "SYSTEM" 
		logData.Msg = "✅ Acesso Autorizado" + vetorAcesso + "."

	} else if auditRegex.MatchString(rawMessage) {
		logData.ID = "AUDIT_MGMT" // Gestão de contas e auditoria
		logData.Severity = "INFO"
		
		contaRegex := regexp.MustCompile(`(?i)Nome da Conta:\s*([a-zA-Z0-9_$-]+)`)
		matches := contaRegex.FindAllStringSubmatch(rawMessage, -1)
		if len(matches) > 1 {
			logData.User = matches[1][1]
		} else if len(matches) > 0 {
			logData.User = matches[0][1]
		} else {
			logData.User = "SYSTEM"
		}
		logData.Msg = "🛡️ Auditoria: Alteração de permissões ou gestão de utilizadores."

	} else {
		logData.ID = "SYS_UNCLASSIFIED" // Log sem padrão mapeado
		logData.Severity = "WARNING"
		logData.User = "UNKNOWN"
		logData.Msg = "⚠️ Evento não classificado/desconhecido capturado."
	}

	// Adicionar o Log Original para auditoria e debug
	logData.Msg = logData.Msg + " | Log Original: " + rawMessage

	// 3. GRAVAÇÃO NA BASE DE DADOS
	err := insertSyslog(logData)
	if err != nil {
		fmt.Printf("❌ [POSTGRES ERROR] Erro ao gravar evento %s: %v\n", logData.ID, err)
	} else {
		fmt.Printf("✅ [POSTGRES SUCCESS] Evento %s gravado!\n", logData.ID)
	}

	// 4. NOTIFICAÇÃO DA API
	// Dispara API para Filament caso seja crítico
	if logData.Severity == "CRITICAL" || logData.Severity == "EMERGENCY" {
		notifyLaravel(logData)
	}
}
