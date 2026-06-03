<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('syslogs', function (Blueprint $table) {
            $table->string('workstation')->nullable()->after('hostname');
            $table->string('workgroup')->nullable()->after('workstation');
        });
    }

    public function down(): void
    {
        Schema::table('syslogs', function (Blueprint $table) {
            $table->dropColumn(['workstation', 'workgroup']);
        });
    }
};