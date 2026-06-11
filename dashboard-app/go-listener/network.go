package main

import (
	"fmt"
	"net"
	"os"
	"strings"
)

type NetworkInfo struct {
	LocalIP     string
	Hostname    string
	Computer    string
	Workstation string
	Workgroup   string
	User        string
	MAC         string
}

func getNetworkInfo(ip string) NetworkInfo {
	return NetworkInfo{
		LocalIP:     ip,
		Hostname:    resolveHostname(ip),
		Computer:    getComputerName(),
		Workstation: getWorkstationName(),
		Workgroup:   getWorkgroupName(),
		User:        getCurrentUser(),
		MAC:         getLocalMAC(),
	}
}

func resolveHostname(ip string) string {
	names, err := net.LookupAddr(ip)
	if err != nil || len(names) == 0 {
		return "unknown"
	}
	return strings.TrimSuffix(names[0], ".")
}

func getComputerName() string {
	name, err := os.Hostname()
	if err != nil {
		return "unknown"
	}
	return name
}

func getWorkstationName() string {
	// Placeholder: Em um ambiente Windows, isso pode ser obtido via API ou variável de ambiente
	return os.Getenv("COMPUTERNAME")
}

func getWorkgroupName() string {
	// Placeholder: Em um ambiente Windows, isso pode ser obtido via API ou variável de ambiente
	return os.Getenv("USERDOMAIN")

}

func getCurrentUser() string {
	user := os.Getenv("USERNAME")
	if user == "" {
		return "unknown"
	}
	return user
}

func getLocalMAC() string {
	ifaces, err := net.Interfaces()
	if err != nil {
		return ""
	}

	for _, i := range ifaces {
		if len(i.HardwareAddr) > 0 {
			return i.HardwareAddr.String()
		}
	}
	return ""
}

func getEnv(key, fallback string) string {
	if value, exists := os.LookupEnv(key); exists {
		return value
	}
	return fallback
}

// StartUDPServer inicializa o listener de Syslog e escuta pacotes de forma assíncrona
func StartUDPServer() (*net.UDPConn, error) {
	port := getEnv("LISTENER_PORT", "1514") // Padrão do teu container interno

	// ⚠️ Mudar de 127.0.0.1 para 0.0.0.0 para aceitar conexões vindas de fora do container
	addr, err := net.ResolveUDPAddr("udp", "0.0.0.0:"+port)
	if err != nil {
		return nil, fmt.Errorf("erro ao resolver endereço UDP: %w", err)
	}

	ser, err := net.ListenUDP("udp", addr)
	if err != nil {
		return nil, fmt.Errorf("erro ao iniciar servidor UDP na porta %s: %w", port, err)
	}

	fmt.Printf("📡 Servidor UDP escutando com sucesso na porta %s em todas as interfaces\n", port)

	// Dispara a goroutine de leitura para não travar a main thread
	go listenLoop(ser)

	return ser, nil
}

// listenLoop processa continuamente os pacotes que entram na placa de rede
func listenLoop(ser *net.UDPConn) {
	// Buffer de 4096 bytes é ideal para a maioria dos payloads XML de Syslog
	p := make([]byte, 4096)

	for {
		n, remoteAddr, err := ser.ReadFromUDP(p)
		if err != nil {
			// Evita flood de logs no console caso a conexão seja fechada intencionalmente
			if netErr, ok := err.(net.Error); ok && netErr.Timeout() {
				continue
			}
			fmt.Printf("⚠️ Erro ao ler dados do UDP: %v\n", err)
			continue
		}

		// Captura o payload bruto
		payload := string(p[:n])
		clientIP := remoteAddr.IP.String()

		// Opcional: Log de depuração para ambiente de desenvolvimento no Windows
		fmt.Printf("📥 Pacote recebido de %s (%d bytes)\n", remoteAddr, n)

		// Envia a string bruta para o canal de processamento do worker
		go ProcessRawLog(payload, clientIP)
	}
}
