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
    Schema::create('licenses', function (Blueprint $table) {
        $table->id();
        // Relacionamento com o departamento (se deletar o departamento, as licenças perdem o vínculo)
        $table->foreignId('department_id')->constrained()->onDelete('cascade');
        
        $table->string('software_name'); // Ex: "Windows 11 Pro", "Office 365", "AutoCAD"
        $table->string('license_key')->nullable(); // A chave de ativação propriamente dita
        $table->string('supplier')->nullable(); // Empresa/Fornecedor que vendeu a licença
        
        $table->integer('total_slots')->default(1); // Quantidade total comprada/contratada
        $table->integer('used_slots')->default(0);  // Quantidade já instalada em computadores
        
        $table->date('purchased_at')->nullable(); // Data da compra/emissão do contrato
        $table->date('expires_at')->nullable();   // Data de expiração (Vitalícia se ficar null)
        
        $table->text('notes')->nullable(); // Observações gerais (Links de contratos, etc.)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
