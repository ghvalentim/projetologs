package main

import (
	"encoding/xml"
	"fmt"
	"strings"
	"time"
)

// SyslogDTO define a estrutura unificada para persistência e mensageria
type SyslogDTO struct {
	EventID     int       `json:"event_id"`
	IPAddress   string    `json:"ip_address"`
	MacAddress  string    `json:"mac_address"`
	Hostname    string    `json:"hostname"`
	Workstation string    `json:"workstation"`
	Workgroup   string    `json:"workgroup"`
	Severity    string    `json:"severity"`
	Username    string    `json:"username"`
	RawMessage  string    `json:"message"`
	ReceivedAt  time.Time `json:"received_at"`
}

// SyslogXML representa o mapeamento direto das tags do XML recebido
type SyslogXML struct {
	XMLName     xml.Name `xml:"log"`
	EventID     int      `xml:"id"`
	IP          string   `xml:"ip"`
	MAC         string   `xml:"mac"`
	Host        string   `xml:"host"`
	Workstation string   `xml:"workstation"`
	Workgroup   string   `xml:"workgroup"`
	Severity    string   `xml:"severity"`
	User        string   `xml:"user"`
	Msg         string   `xml:"msg"`
}

// parseXMLToDTO processa a string XML e retorna o DTO estruturado
func parseXMLToDTO(rawXML string) (SyslogDTO, bool) {
	var xmlData SyslogXML

	// Remove possíveis espaços ou quebras de linha antes do parse
	cleanedXML := strings.TrimSpace(rawXML)

	err := xml.Unmarshal([]byte(cleanedXML), &xmlData)
	if err != nil {
		fmt.Printf("⚠️ Erro no Unmarshal do XML: %v\n", err)
		return SyslogDTO{}, false
	}

	dto := SyslogDTO{
		EventID:     xmlData.EventID,
		IPAddress:   xmlData.IP,
		MacAddress:  xmlData.MAC,
		Hostname:    xmlData.Host,
		Workstation: xmlData.Workstation,
		Workgroup:   xmlData.Workgroup,
		Severity:    xmlData.Severity,
		Username:    xmlData.User,
		RawMessage:  xmlData.Msg,
		ReceivedAt:  time.Now(), // Define o timestamp do recebimento no servidor
	}

	return dto, true
}
