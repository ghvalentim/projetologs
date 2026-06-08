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
        Schema::table('syslogs', function (Blueprint $table) {
            // O ->change() converte a coluna de Inteiro para String (VARCHAR)
            $table->string('event_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('syslogs', function (Blueprint $table) {
            $table->integer('event_id')->change();
        });
    }
};
