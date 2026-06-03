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
func insertSyslog(dto SyslogData) error {
	// Query ajustada com os nomes exatos das colunas da tabela gerada pelas migrations do Laravel/Filament
	// Inclui os timestamps padrão (created_at e updated_at) para o Laravel não reclamar
	query := `
		INSERT INTO syslogs 
		(id_evento, ip_origem, mac_origem, hostname, workstation, workgroup, severity, username, mensagem, created_at, updated_at) 
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)
	`
	now := time.Now()
	_, err := db.Exec(
		query,
		dto.ID,
		dto.IP,
		dto.Mac,
		dto.Host,
		dto.Workstation,
		dto.Workgroup,
		dto.Severity,
		dto.User,
		dto.Msg,
		now, // created_at
		now, // updated_at
	)

	return err
}
