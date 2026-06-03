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
    Schema::create('departments', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Ex: "Agrupamento Escolar de Oliveira do Hospital", "Secretaria de Saúde"
        $table->string('type')->nullable(); // Ex: "Educação", "Saúde", "Administrativo", "Gabinete"
        $table->string('responsible_person')->nullable(); // Nome do Diretor ou Técnico responsável no local
        $table->string('contact_email')->nullable(); // Email para alertas de vencimento direcionados
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
