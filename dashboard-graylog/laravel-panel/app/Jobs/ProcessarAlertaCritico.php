<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\AlertaSegurancaCritico;

class ProcessarAlertaCritico implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $syslogData;

    public function __construct(array $syslogData)
    {
        $this->syslogData = $syslogData;
    }

    public function handle(): void
    {
        $eventId = $this->syslogData['event_id'] ?? 0;
        $username = $this->syslogData['username'] ?? 'Desconhecido';
        $ip = $this->syslogData['ip_address'] ?? '127.0.0.1';

        // 🧠 A RESPONSABILIDADE DE FORMATAÇÃO E REGRAS VIVE AQUI!
        $mensagemFormatada = "";

        switch ($eventId) {
            case 4625:
                $mensagemFormatada = "Alerta de Segurança: Tentativa de login FALHADA para o utilizador [{$username}] com origem no IP {$ip}.";
                break;
            
            // Se um dia adicionares logs da firewall ou outros IDs:
            case 2004: 
            case 2006:
                $mensagemFormatada = "Infraestrutura: Conexão de rede bloqueada pela Firewall com origem em {$ip}.";
                break;

            default:
                $mensagemFormatada = "Log de Auditoria Geral recebido do sistema.";
                break;
        }

        // Agora guardas ou disparas o evento formatado para o teu Tauri/Livewire
        Log::info("📢 [SIEM CORE] Evento Processado pelo Laravel: " . $mensagemFormatada);

        // Atualiza a tabela com a mensagem amigável final ou transmite via Redis para o front-end

        Mail::to('admin@empresa.com')->send(new AlertaSegurancaCritico($this->syslogData));
    }
}