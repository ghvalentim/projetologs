<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaSegurancaCritico extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $logDados) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "🚨 ALERTA CRÍTICO: Tentativa de Intrusão detetada em " . $this->logDados['hostname'],
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <h2>Alerta de Segurança do Sistema</h2>
                <p>O Agente Go intercetou uma atividade de alto risco no Windows Event Viewer.</p>
                <hr>
                <ul>
                    <li><strong>Evento:</strong> ID {$this->logDados['event_id']} (Falha de Autenticação)</li>
                    <li><strong>Utilizador Alvo:</strong> {$this->logDados['username']}</li>
                    <li><strong>IP de Origem:</strong> {$this->logDados['ip_address']}</li>
                    <li><strong>Endereço MAC:</strong> {$this->logDados['mac_address']}</li>
                    <li><strong>Máquina:</strong> {$this->logDados['hostname']}</li>
                    <li><strong>Data/Hora:</strong> {$this->logDados['received_at']}</li>
                </ul>
                <hr>
                <p><em>Ação recomendada: Verificar de imediato as diretivas de isolamento de rede para o IP afetado.</em></p>
            ",
        );
    }

}