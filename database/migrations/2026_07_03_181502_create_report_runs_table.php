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
        Schema::create('report_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('saved_report_id')->nullable()->constrained('saved_reports')->nullOnDelete();
            $table->string('report_type');
            $table->string('status')->default('running');
            $table->json('filters_json')->nullable();
            $table->json('result_summary_json')->nullable();
            $table->json('warnings_json')->nullable();
            $table->json('errors_json')->nullable();
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('saved_report_id');
            $table->index('report_type');
            $table->index('status');
            $table->index('started_by_user_id');
            $table->index('started_at');
            $table->index('finished_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_runs');
    }
};
