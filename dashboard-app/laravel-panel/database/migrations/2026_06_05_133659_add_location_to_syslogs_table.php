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
        $table->string('country')->nullable();
        $table->string('city')->nullable();
        $table->string('latitude')->nullable();
        $table->string('longitude')->nullable();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('syslogs', function (Blueprint $table) {
            //
        });
    }
};
