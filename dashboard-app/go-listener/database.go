package main

import (
	"database/sql"
	"fmt"
	"time"

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

// insertSyslog atualizado para receber o novo DTO SyslogData normalizado pelo Regex
// insertSyslog corrigido com as colunas reais da tua tabela Postgres
// insertSyslog ajustado milimetricamente para as colunas em inglês do teu Laravel
func insertSyslog(dto SyslogData) error {
	query := `
		INSERT INTO syslogs 
		(event_id, username, ip_address, mac_address, hostname, workstation, workgroup, severity, message, received_at) 
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
	`
	now := time.Now()
	_, err := db.Exec(
		query,
		dto.ID,          // event_id -> $1
		dto.User,        // username -> $2
		dto.IP,          // ip_address -> $3
		dto.Mac,         // mac_address -> $4
		dto.Host,        // hostname -> $5
		dto.Workstation, // workstation -> $6
		dto.Workgroup,   // workgroup -> $7
		dto.Severity,    // severity -> $8
		dto.Msg,         // message -> $9
		now,             // received_at -> $10
	)

	// ⚠️ DEFEITO DETETADO: Se houver erro, vamos printar FORÇADAMENTE no terminal!
	if err != nil {
		fmt.Printf("❌ [POSTGRES ERROR] Falha crítica no INSERT: %v\n", err)
	} else {
		fmt.Println("✅ [POSTGRES SUCCESS] Log gravado na base de dados central com sucesso!")
	}

	return err
}
