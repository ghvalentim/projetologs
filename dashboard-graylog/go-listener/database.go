package main

import (
	"database/sql"
	"fmt"

	_ "github.com/jackc/pgx/v5/stdlib"
)

var db *sql.DB

func initDB() {
	host := getEnv("DB_HOST", "127.0.0.1")
	dsn := fmt.Sprintf(
		"host=%s port=%s user=%s password=%s dbname=%s sslmode=disable",
		host,
		getEnv("DB_PORT", "5432"),
		getEnv("DB_USER", "syslog_user"),
		getEnv("DB_PASSWORD", "syslog_password"),
		getEnv("DB_NAME", "syslog_db"),
	)

	var err error
	db, err = sql.Open("pgx", dsn)
	if err != nil {
		panic(fmt.Sprintf("Erro na abertura do banco: %v", err))
	}

	if err := db.Ping(); err != nil {
		panic(fmt.Sprintf("Banco inacessível: %v", err))
	}
	fmt.Println("✅ PostgreSQL conectado com sucesso")
}

func insertSyslog(dto SyslogDTO) {
	query := `
		INSERT INTO syslogs 
		(event_id, ip_address, mac_address, hostname, workstation, workgroup, severity, username, message, received_at) 
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
	`
	_, err := db.Exec(
		query,
		dto.EventID,
		dto.IPAddress,
		dto.MacAddress,
		dto.Hostname,
		dto.Workstation,
		dto.Workgroup,
		dto.Severity,
		dto.Username,
		dto.RawMessage,
		dto.ReceivedAt,
	)
	if err != nil {
		fmt.Printf("❌ Erro ao inserir log no Postgres: %v\n", err)
	}
}
