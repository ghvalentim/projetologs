<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Jobs\ProcessarAlertaCritico;

class Syslog extends Model
{
    protected $table = 'syslogs';

    public $timestamps = false;

    /**
     * Os atributos que podem ser atribuídos em massa.
     * Atualizado com os novos campos enviados pelo Agente Go.
     */
    protected $fillable = [
        'event_id',
        'ip_address',
        'mac_address',
        'hostname',
        'workstation', // 🆕 Adicionado
        'workgroup',   // 🆕 Adicionado
        'severity',
        'username',
        'message', 
        'received_at'
    ];

    protected function casts(): array
    {
        return [
            'event_id' => 'integer',
            'received_at' => 'datetime',
        ];
    }

    /**
     * 🧠 ACESSOR: Responsabilidade de Apresentação Única do Laravel.
     */
    protected function mensagemFormatada(): Attribute
    {
        return Attribute::get(function () {
            $username = $this->username ?? 'Desconhecido';
            $ip = $this->ip_address ?? '127.0.0.1';
            $maquinaOrigem = $this->workstation ?? $this->hostname ?? 'Host Desconhecido';

            return match ($this->event_id) {
                4625 => "🚨 Alerta de Segurança: Tentativa de autenticação FALHADA para o utilizador [{$username}] na estação [{$maquinaOrigem}] com origem no IP {$ip}.",
                
                // IDs comuns de monitorização de rede ou regras de firewall do Windows
                2004, 2006, 5152 => "⚠️ Firewall: Uma ligação de rede com origem em {$ip} foi bloqueada pelas diretivas do sistema.",
                
                default => !empty($this->username) 
                    ? "ℹ️ Auditoria: Atividade registada para o utilizador [{$username}] no host {$this->hostname} (Workstation: {$this->workstation})."
                    : "ℹ️ Sistema: Log de auditoria geral processado para o IP {$ip}."
            };
        });
    }

    protected static function booted(): void
    {
        static::created(function (Syslog $syslog) {
            if ($syslog->severity === 'CRITICAL' || $syslog->event_id === 4625) {
                
                ProcessarAlertaCritico::dispatch([
                    'event_id'    => $syslog->event_id,
                    'username'    => $syslog->username,
                    'ip_address'  => $syslog->ip_address,
                    'mac_address' => $syslog->mac_address,
                    'hostname'    => $syslog->hostname,
                    'workstation' => $syslog->workstation, // Passar também para a fila do Redis se necessário
                    'workgroup'   => $syslog->workgroup,   // Passar também para a fila do Redis se necessário
                    'received_at' => $syslog->received_at?->toIso8601String(),
                ]);
            }
        });
    }
}