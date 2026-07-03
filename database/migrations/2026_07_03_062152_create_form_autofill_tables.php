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
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('context_type');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('format_type');
            $table->string('version');
            $table->json('fields_schema_json')->nullable();
            $table->json('mapping_rules_json')->nullable();
            $table->json('validation_rules_json')->nullable();
            $table->json('renderer_config_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'code']);
            $table->index(['context_type', 'format_type']);
        });

        Schema::create('form_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->string('field_key');
            $table->string('label');
            $table->string('field_type');
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules_json')->nullable();
            $table->text('ai_extraction_hint')->nullable();
            $table->json('default_value_json')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['form_template_id', 'field_key']);
            $table->index(['form_template_id', 'sort_order']);
        });

        Schema::create('form_autofill_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_email_extraction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('raw_input_hash')->nullable();
            $table->json('suggested_values_json')->nullable();
            $table->json('validation_errors_json')->nullable();
            $table->json('warnings_json')->nullable();
            $table->json('user_changes_json')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['email_message_id', 'form_template_id']);
        });

        Schema::create('form_autofill_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_autofill_run_id')->constrained()->cascadeOnDelete();
            $table->string('field_key');
            $table->text('extracted_value')->nullable();
            $table->text('normalized_value')->nullable();
            $table->text('final_value')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('source_excerpt')->nullable();
            $table->boolean('requires_review')->default(false);
            $table->string('review_reason')->nullable();
            $table->foreignId('accepted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['form_autofill_run_id', 'field_key']);
        });

        Schema::create('form_autofill_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_autofill_run_id')->constrained()->cascadeOnDelete();
            $table->string('output_type');
            $table->string('filename')->nullable();
            $table->string('stored_path')->nullable();
            $table->json('content_json')->nullable();
            $table->string('status');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_autofill_outputs');
        Schema::dropIfExists('form_autofill_field_values');
        Schema::dropIfExists('form_autofill_runs');
        Schema::dropIfExists('form_template_fields');
        Schema::dropIfExists('form_templates');
    }
};
