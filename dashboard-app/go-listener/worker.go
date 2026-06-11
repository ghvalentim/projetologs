package main

import (
	"fmt"
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

	// 1. Inicialização Base
	logData := SyslogData{
		IP:          clientIP,
		Mac:         "N/A",
		Workgroup:   "ESTGOH-NETWORK",
		Severity:    "INFO",
		Workstation: "NETWORK-DEVICE",
		Host:        "UNKNOWN",
	}

	// 2. Extração de Hostname nativo
	words := strings.Fields(rawMessage)
	if len(words) > 3 {
		logData.Host = words[3]
		logData.Workstation = words[3]
	}

	// 3. Localização Geográfica
	country, city, lat, lon := geolocateIP(clientIP)
	logData.Country = country
	logData.City = city
	logData.Latitude = lat
	logData.Longitude = lon

	// 👇 4. A MAGIA: Chamamos o ficheiro externo para analisar e preencher as mensagens!
	ClassifyLog(rawMessage, &logData)

	// Anexa a mensagem original para fins de auditoria no painel
	logData.Msg = logData.Msg + " | Log Original: " + rawMessage

	// 5. Gravação na Base de Dados
	err := insertSyslog(logData)
	if err != nil {
		fmt.Printf("❌ [POSTGRES ERROR] Erro ao gravar evento %s: %v\n", logData.ID, err)
	} else {
		fmt.Printf("✅ [POSTGRES SUCCESS] Evento %s gravado!\n", logData.ID)
	}

	// 6. Notificação (Laravel API)
	if logData.Severity == "CRITICAL" || logData.Severity == "EMERGENCY" {
		notifyLaravel(logData)
	}
}
