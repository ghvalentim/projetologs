package main

import "fmt"

// Certifique-se de que o canal e a estrutura DTO estejam definidos globalmente ou no main.go
var logChannel = make(chan string, 100)

func logWorker() {
	fmt.Println("🚀 Worker iniciado e escutando canal de logs...")
	for xmlContent := range logChannel {
		dto, ok := parseXMLToDTO(xmlContent)
		if !ok {
			fmt.Println("⚠️ Falha ao parsear XML recebido.")
			continue
		}

		// Fluxo 1: Redis (Tempo Real -> Laravel)
		publishToRedis(dto)

		// Fluxo 2: Postgres (Histórico)[cite: 1]
		insertSyslog(dto)
	}
}
