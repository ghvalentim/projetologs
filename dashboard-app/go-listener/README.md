# Go Listener (Syslog & Event Normalizer)

Este microsserviço atua como o motor de ingestão de dados do sistema. Desenvolvido em **Go**, foi desenhado para ter uma pegada de memória reduzida e alta performance na receção contínua de logs.

## 🔧 Funcionalidades

* Escuta ativa de eventos de segurança provenientes de máquinas Windows.
* Tratamento e normalização dos dados recebidos (Parsing).
* Ligação direta e armazenamento estruturado na base de dados relacional, permitindo o consumo imediato por parte do painel Laravel.

## 💻 Execução Isolada (Sem Docker)

Caso pretendas testar o listener localmente sem a orquestração do Docker:

```bash
go mod tidy
go run main.go