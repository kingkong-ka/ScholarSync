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
        Schema::table('violation_types', function (Blueprint $table) {
            $table->string('offenses')->nullable()->after('default_penalty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('violation_types', function (Blueprint $table) {
            $table->dropColumn('offenses');
        });
    }
};
