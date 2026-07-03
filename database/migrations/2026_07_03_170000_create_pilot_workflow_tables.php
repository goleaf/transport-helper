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
        if (! Schema::hasTable('pilot_suppliers')) {
            Schema::create('pilot_suppliers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('status')->default('draft');
                $table->text('description')->nullable();
                $table->json('data_sources_json')->nullable();
                $table->json('import_mappings_json')->nullable();
                $table->json('manufacturer_form_mapping_json')->nullable();
                $table->json('email_sample_mapping_json')->nullable();
                $table->json('carrier_mapping_json')->nullable();
                $table->json('logistics_mapping_json')->nullable();
                $table->json('uat_checklist_json')->nullable();
                $table->json('readiness_result_json')->nullable();
                $table->json('dry_run_result_json')->nullable();
                $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('company_id');
                $table->index('supplier_id');
                $table->index('status');
                $table->index('approved_by_user_id');
            });
        }

        if (! Schema::hasTable('pilot_files')) {
            Schema::create('pilot_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pilot_supplier_id')->constrained()->cascadeOnDelete();
                $table->string('file_type');
                $table->string('original_filename');
                $table->string('stored_path');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->string('checksum')->nullable();
                $table->json('metadata_json')->nullable();
                $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('pilot_supplier_id');
                $table->index('file_type');
                $table->index('checksum');
                $table->index('uploaded_by_user_id');
            });
        }

        if (! Schema::hasTable('pilot_runs')) {
            Schema::create('pilot_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pilot_supplier_id')->constrained()->cascadeOnDelete();
                $table->string('run_type');
                $table->string('status');
                $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->json('result_json')->nullable();
                $table->json('warnings_json')->nullable();
                $table->json('errors_json')->nullable();
                $table->timestamps();

                $table->index('pilot_supplier_id');
                $table->index('run_type');
                $table->index('status');
                $table->index('started_by_user_id');
                $table->index('started_at');
                $table->index('finished_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pilot_runs');
        Schema::dropIfExists('pilot_files');
        Schema::dropIfExists('pilot_suppliers');
    }
};
