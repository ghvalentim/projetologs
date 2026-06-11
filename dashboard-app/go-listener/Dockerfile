# Estágio 1: Compilação
FROM golang:1.26-alpine AS builder

WORKDIR /app

# Copia os arquivos de pacotes já prontos da sua máquina
COPY go.mod go.sum ./

# Baixa exatamente o que está travado no .mod anterior
RUN go mod download

# Copia o código fonte
COPY . .

# Compila direto sem inventar moda
RUN CGO_ENABLED=0 GOOS=linux go build -ldflags="-w -s" -o syslog-listener .

# Estágio 2: Execução
FROM alpine:latest
WORKDIR /root/
RUN apk --no-cache add ca-certificates
COPY --from=builder /app/syslog-listener .
EXPOSE 514/udp
CMD ["./syslog-listener"]