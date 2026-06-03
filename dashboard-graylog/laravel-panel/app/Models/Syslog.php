<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Jobs\ProcessarAlertaCritico;

class Syslog extends Model
{
    /**
     * O nome da tabela associada ao modelo na base de dados PostgreSQL.
     *
     * @var string
     */
    protected $table = 'syslogs';

    /**
     * Desativa os timestamps padrão do Laravel (created_at e updated_at).
     * Como o Agente Go faz a ingestão direta com a coluna 'received_at',
     * evitamos que o Eloquent tente injetar campos inexistentes na tabela.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Os atributos que podem ser atribuídos em massa (Mass Assignment).
     * Totalmente mapeados de acordo com o DTO normalizado enviado pelo Agente Go.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'ip_address',
        'mac_address',
        'hostname',
        'severity',
        'username',
        'message', // Contém o XML bruto / conteúdo original para auditoria
        'received_at'
    ];

    /**
     * Mapeamento e conversão dos tipos de dados nativos do Laravel.
     * Ajustado para o novo padrão de definição de Casts em método.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_id' => 'integer',
            'received_at' => 'datetime',
        ];
    }

    /**
     * 🧠 ACESSOR: Responsabilidade de Apresentação Única do Laravel.
     * Converte os códigos brutos de infraestrutura e IDs numa string humanizada em PT-PT.
     * No Filament v3 invocamos isto simplesmente através de: TextColumn::make('mensagem_formatada')
     */
    protected function mensagemFormatada(): Attribute
    {
        return Attribute::get(function () {
            $username = $this->username ?? 'Desconhecido';
            $ip = $this->ip_address ?? '127.0.0.1';

            return match ($this->event_id) {
                4625 => "🚨 Alerta de Segurança: Tentativa de autenticação FALHADA para o utilizador [{$username}] com origem no IP {$ip}.",
                
                // IDs comuns de monitorização de rede ou regras de firewall do Windows
                2004, 2006, 5152 => "⚠️ Firewall: Uma ligação de rede com origem em {$ip} foi bloqueada pelas diretivas do sistema.",
                
                default => !empty($this->username) 
                    ? "ℹ️ Auditoria: Atividade registada para o utilizador [{$username}] no host {$this->hostname}."
                    : "ℹ️ Sistema: Log de auditoria geral processado para o IP {$ip}."
            };
        });
    }

    /**
     * O bloco de inicialização do modelo (Booted).
     * Regista de forma reativa os gatilhos (Model Observers).
     */
    protected static function booted(): void
    {
        /**
         * Evento 'created': Disparado imediatamente após o commit de um log na BD.
         * Envia os metadados puros estruturados para o Redis tratar em background.
         */
        static::created(function (Syslog $syslog) {
            // Se for um ataque crítico (ID 4625) ou severidade CRITICAL marcada pelo coletor Go
            if ($syslog->severity === 'CRITICAL' || $syslog->event_id === 4625) {
                
                // 🚀 Despacha o DTO limpo para a memória RAM (Redis Queue)
                ProcessarAlertaCritico::dispatch([
                    'event_id'    => $syslog->event_id,
                    'username'    => $syslog->username,
                    'ip_address'  => $syslog->ip_address,
                    'mac_address' => $syslog->mac_address,
                    'hostname'    => $syslog->hostname,
                    'received_at' => $syslog->received_at?->toIso8601String(),
                ]);
            }
        });
    }
}