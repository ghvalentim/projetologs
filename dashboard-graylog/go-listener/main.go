package main

import (
	"fmt"
	"os"
	"os/signal"
	"syscall"
)

func main() {
	fmt.Println("🌀 Inicializando Go Syslog Listener...")

	// 1. Inicializa conexões com os serviços (Postgres e Redis no Docker/WSL)
	initDB()
	initRedis()

	// 2. Dispara o Worker para processar a fila concorrentemente
	go logWorker()

	// 3. Inicia o servidor UDP delegado ao network.go
	conn, err := StartUDPServer()
	if err != nil {
		panic(fmt.Sprintf("❌ Falha crítica ao iniciar serviço de rede: %v", err))
	}
	defer conn.Close()

	// 4. Mecanismo de Graceful Shutdown (Captura Ctrl+C ou encerramento do terminal)
	stop := make(chan os.Signal, 1)
	signal.Notify(stop, os.Interrupt, syscall.SIGTERM)

	<-stop
	fmt.Println("\n🛑 Encerrando o Listener graciosamente...")

	// Fecha o canal para que o worker termine de processar os logs restantes antes de morrer
	close(logChannel)
	fmt.Println("👋 Serviço finalizado com sucesso.")
}
