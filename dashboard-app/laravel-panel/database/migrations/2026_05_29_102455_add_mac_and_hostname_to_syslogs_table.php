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
            $table->string('mac_address', 17)->nullable()->after('ip_address');
        $table->string('hostname')->nullable()->after('mac_address');
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
