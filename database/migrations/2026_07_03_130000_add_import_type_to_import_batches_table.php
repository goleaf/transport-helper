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
        Schema::table('import_batches', function (Blueprint $table): void {
            if (! Schema::hasColumn('import_batches', 'import_type')) {
                $table->string('import_type')->nullable()->after('company_id');
                $table->index('import_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table): void {
            if (Schema::hasColumn('import_batches', 'import_type')) {
                $table->dropIndex(['import_type']);
                $table->dropColumn('import_type');
            }
        });
    }
};
