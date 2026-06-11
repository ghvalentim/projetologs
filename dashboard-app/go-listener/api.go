package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"net/http"
	"strings"
)

type GeoIP struct{
	Status string `json:"status"`
	Country string `json:"country"`
	City string `json:"city"`
	Lat float64 `json:"lat"`
	Lon float64 `json:"lon"`
}

func geolocateIP(ip string) (string, string, string, string) {
	// Ignora IPs locais para não banirem o teu IP na API gratuita
	if strings.HasPrefix(ip, "192.168.") || strings.HasPrefix(ip, "10.") || strings.HasPrefix(ip, "127.") {
		return "Portugal", "Oliveira do Hospital (Rede Local)", "40.359", "-7.861" // As coordenadas da CMOH ;)
	}

	resp, err := http.Get(fmt.Sprintf("http://ip-api.com/json/%s", ip))
	if err != nil {
		return "Unknown", "Unknown", "0", "0"
	}
	defer resp.Body.Close()

	var geo GeoIP
	if err := json.NewDecoder(resp.Body).Decode(&geo); err != nil || geo.Status != "success" {
		return "Unknown", "Unknown", "0", "0"
	}

	return geo.Country, geo.City, fmt.Sprintf("%f", geo.Lat), fmt.Sprintf("%f", geo.Lon)
}

func notifyLaravel(dto SyslogData) {
    // Filtro de performance: Só avança se for grave
    if dto.Severity != "CRITICAL" && dto.Severity != "EMERGENCY" {
        return
    }

    payload, err := json.Marshal(dto)
    if err != nil {
        fmt.Println("❌ Erro ao estruturar JSON:", err)
        return
    }

    // Bate direto no container do Laravel através da rede interna do Docker
    resp, err := http.Post("http://laravel-app:8000/api/syslog/notify", "application/json", bytes.NewBuffer(payload))
    if err != nil {
        fmt.Println("❌ Falha ao notificar a API do Laravel:", err)
        return
    }
    defer resp.Body.Close()

    fmt.Printf("🚀 [API] Alerta nível %s despachado para o painel! Status: %d\n", dto.Severity, resp.StatusCode)
}

