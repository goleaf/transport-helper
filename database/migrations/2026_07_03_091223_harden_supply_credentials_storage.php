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
        Schema::table('email_accounts', function (Blueprint $table): void {
            $table->longText('encrypted_config')->nullable()->change();
        });

        Schema::table('integration_connections', function (Blueprint $table): void {
            $table->longText('encrypted_config')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table): void {
            $table->json('encrypted_config')->nullable()->change();
        });

        Schema::table('integration_connections', function (Blueprint $table): void {
            $table->json('encrypted_config')->nullable()->change();
        });
    }
};
