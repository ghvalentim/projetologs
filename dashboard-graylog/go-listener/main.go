package main

import (
	"fmt"
	"os"
	"os/signal"
	"syscall"
)

func main() {
	fmt.Println("🌀 Inicializando Go Syslog Listener...")

	// 1. Inicializa conexões com os serviços (Postgres e Redis no Docker)
	initDB()
	initRedis()

	// 2. Inicia o servidor UDP (o próprio network.go vai disparar o listenLoop em background)
	ser, err := StartUDPServer()
	if err != nil {
		panic(fmt.Sprintf("❌ Falha crítica ao iniciar serviço de rede: %v", err))
	}
	defer ser.Close()

	// 3. Mecanismo de Graceful Shutdown (Captura Ctrl+C ou encerramento do Docker)
	stop := make(chan os.Signal, 1)
	signal.Notify(stop, os.Interrupt, syscall.SIGTERM)

	<-stop
	fmt.Println("\n🛑 Encerrando o Listener graciosamente...")
	fmt.Println("👋 Serviço finalizado com sucesso.")
}
