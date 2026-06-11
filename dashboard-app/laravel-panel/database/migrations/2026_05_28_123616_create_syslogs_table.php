<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('syslogs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45); // Suporta IPv4 e IPv6
            $table->string('severity', 20);   // INFO, WARNING, ERROR, etc.
            $table->text('message');          // O texto completo do log
            $table->timestamp('received_at')->useCurrent(); // Data/Hora do recebimento
            
            // Cria um índice na data para buscas e paginações ficarem ultra rápidas
            $table->index('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('syslogs');
    }
};