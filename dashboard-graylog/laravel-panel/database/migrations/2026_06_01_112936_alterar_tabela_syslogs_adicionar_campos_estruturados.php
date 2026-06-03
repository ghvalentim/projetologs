<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('syslogs', function (Blueprint $table) {
            // Adiciona os novos campos estruturados vindos do DTO do Go
            $table->integer('event_id')->nullable()->after('id');
            $table->string('username')->nullable()->after('severity');
            
            // Garantir que a coluna message aceita o XML bruto grande (TEXT)
            $table->text('message')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('syslogs', function (Blueprint $table) {
            $table->dropColumn(['event_id', 'username']);
        });
    }
};