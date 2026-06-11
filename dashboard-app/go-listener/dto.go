package main

import (
	"encoding/xml"
	"fmt"
	"regexp"
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

var (
	ruidoDockerRegex = regexp.MustCompile(`(?i)(docker|containerd|veth|NetworkManager|wsl)`)
	authFailRegex    = regexp.MustCompile(`(?i)(4625|LOGIN_FAILED|Authentication\s+failed|failed\s+login)`)
	successRegex     = regexp.MustCompile(`(?i)(4624|LOGIN_SUCCESS|Accepted|session\s+opened)`)
	attackRegex      = regexp.MustCompile(`(?i)(PortScan|BruteForce|Attack|\bDoS\b|\bdrop\b|\bdeny\b)`)
	
	rdpRegex         = regexp.MustCompile(`(?i)(1149|TerminalServices|Logon Type:\s*10)`)
	sshRegex         = regexp.MustCompile(`(?i)(OpenSSH|sshd)`)
	privilegeRegex   = regexp.MustCompile(`(?i)(4648|4672|4673|Explicit credentials|Privileged Service|Special privileges|privilégios especiais)`)
	psRegex          = regexp.MustCompile(`(?i)(4104|PowerShell)`)
	auditRegex       = regexp.MustCompile(`(?i)(4720|4722|4732|4799|Security-Auditing|enumerada|adesão ao grupo)`)

	routerDropRegex  = regexp.MustCompile(`(?i)(DROP|DENY|block|reject).*(IN=|OUT=|src=|dst=)`)
	routerDhcpRegex  = regexp.MustCompile(`(?i)(DHCPACK|DHCPDISCOVER|DHCPOFFER|DHCPREQUEST|assigned|lease)`)
	routerLinkRegex  = regexp.MustCompile(`(?i)(link up|link down|interface.*changed state)`)
	routerVpnRegex   = regexp.MustCompile(`(?i)(IPsec|OpenVPN|WireGuard|VPN.*connected|VPN.*disconnected)`)
)

func ClassifyLog(rawMessage string, logData *SyslogData) {
	// Identificar vetor de entrada
	vetorAcesso := ""
	if rdpRegex.MatchString(rawMessage) {
		vetorAcesso = " via RDP (Remote Desktop)"
	} else if sshRegex.MatchString(rawMessage) {
		vetorAcesso = " via terminal SSH"
	}

	// Lógica de Triagem
	if ruidoDockerRegex.MatchString(rawMessage) {
		logData.ID = "SYS_DOCKER"
		logData.Severity = "AUDIT"
		logData.User = "SYSTEM"
		logData.Msg = "🐳 Auditoria de Sistema: Processo interno de rede/container."

	} else if routerDropRegex.MatchString(rawMessage) {
		logData.ID = "EDGE_DROP"
		logData.Severity = "WARNING"
		logData.User = "FIREWALL_EDGE"
		if matches := regexp.MustCompile(`(?i)SRC=([0-9\.]+)`).FindStringSubmatch(rawMessage); len(matches) > 1 {
			logData.Msg = "🛡️ Firewall Roteador: Tráfego bloqueado vindo do IP " + matches[1] + "."
		} else {
			logData.Msg = "🛡️ Firewall Roteador: Pacote não autorizado bloqueado na fronteira."
		}

	} else if authFailRegex.MatchString(rawMessage) {
		logData.ID = "4625"
		logData.Severity = "CRITICAL"
		if matches := regexp.MustCompile(`(?i)(?:user|utilizador|Account Name|Nome da Conta)[:\s"']*\s*([a-zA-Z0-9._-]+)`).FindStringSubmatch(rawMessage); len(matches) > 1 {
			logData.User = matches[1]
		} else {
			logData.User = "UNKNOWN_TARGET"
		}
		logData.Msg = "🚨 Falha de Autenticação" + vetorAcesso + " detetada."

	} else if privilegeRegex.MatchString(rawMessage) {
		if regexp.MustCompile(`(?i)Nome da Conta:\s*SYSTEM`).MatchString(rawMessage) {
			logData.ID = "SYS_PRIV_AUTO"
			logData.Severity = "INFO"
			logData.User = "SYSTEM"
			logData.Msg = "ℹ️ Atribuição de privilégios de rotina (Processo do Sistema)."
		} else if regexp.MustCompile(`(?i)(MicrosoftAccount|gabrielvalentimcarvalho@gmail\.com)`).MatchString(rawMessage) {
			logData.ID = "ADMIN_LOGIN_PRIV"
			logData.Severity = "AUDIT"
			matches := regexp.MustCompile(`(?i)Nome da Conta:\s*([a-zA-Z0-9_$-.@]+)`).FindAllStringSubmatch(rawMessage, -1)
			if len(matches) > 1 {
				logData.User = matches[1][1]
			} else if len(matches) > 0 {
				logData.User = matches[0][1]
			} else {
				logData.User = "ADMIN_LOCAL"
			}
			logData.Msg = "🛡️ Auditoria: Privilégios de Administrador atribuídos no login (Comportamento normal)."
		} else if regexp.MustCompile(`(?i)(Explicit credentials|Privilégios Especiais|Special privileges|Privileged Service)`).MatchString(rawMessage) {
			logData.ID = "PRIV_ESCALATION"
			logData.Severity = "EMERGENCY"
			matches := regexp.MustCompile(`(?i)Nome da Conta:\s*([a-zA-Z0-9_$-.@]+)`).FindAllStringSubmatch(rawMessage, -1)
			if len(matches) > 1 {
				logData.User = matches[1][1]
			} else if len(matches) > 0 {
				logData.User = matches[0][1]
			} else {
				logData.User = "UNKNOWN"
			}
			logData.Msg = "⚠️ Abuso de Privilégio: Tentativa humana de usar permissões reservadas."
		}  else if successRegex.MatchString(rawMessage) {
			if regexp.MustCompile(`(?i)MicrosoftAccount|user`).MatchString(rawMessage) {
			logData.ID = "4624"
			logData.Severity = "SUCCESS"
			matches := regexp.MustCompile(`(?i)Nome da Conta:\s*([a-zA-Z0-9_$-.@]+)`).FindAllStringSubmatch(rawMessage, -1)
			if len(matches) > 1 {
				logData.User = matches[1][1]
			} else if len(matches) > 0 {
				logData.User = matches[0][1]
			} else {
				logData.User = "DEFAULT_USER"
			}
			logData.Msg = "✅ Sucesso de Login" + vetorAcesso + " detetado."
			}
		} else if auditRegex.MatchString(rawMessage) {
			logData.ID = "AUDIT_MGMT"
			logData.Severity = "AUDIT"
			matches := regexp.MustCompile(`(?i)Nome da Conta:\s*([a-zA-Z0-9_$-.@]+)`).FindAllStringSubmatch(rawMessage, -1)
			if len(matches) > 1 {
				logData.User = matches[1][1]
			} else if len(matches) > 0 {
				logData.User = matches[0][1]
			} else {
				logData.User = "UNKNOWN"
			}
			logData.Msg = "🛡️ Log de Auditoria: Atividade registada para o utilizador."
		} else if attackRegex.MatchString(rawMessage) {
		logData.ID = "POTENTIAL_ATTACK"
		logData.Severity = "CRITICAL"
		logData.User = "UNKNOWN"
		logData.Msg = "🚨 Potencial Ataque Detetado: Padrão suspeito identificado no log."
		} else if routerDhcpRegex.MatchString(rawMessage) {
		logData.ID = "ROUTER_DHCP"
		logData.Severity = "INFO"
		logData.User = "DHCP_SERVICE"
		logData.Msg = "📡 DHCP: Evento relacionado a atribuição ou renovação de IP detectado no roteador."
		} else if routerLinkRegex.MatchString(rawMessage) {
		logData.ID = "ROUTER_LINK"
		logData.Severity = "INFO"
		logData.User = "NETWORK_INTERFACE"
		logData.Msg = "🔗 Link de Rede: Alteração de estado da interface detectada no roteador."
		} else if routerVpnRegex.MatchString(rawMessage) {
		logData.ID = "ROUTER_VPN"
		logData.Severity = "INFO"
		logData.User = "VPN_SERVICE"
		logData.Msg = "🔒 VPN: Evento relacionado a conexões VPN detectado no roteador."
		} else {
		logData.ID = "SYS_UNCLASSIFIED"
		logData.Severity = "WARNING"
		logData.User = "UNKNOWN"
		logData.Msg = "⚠️ Evento não classificado/desconhecido capturado."
	};
}}