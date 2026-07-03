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
        if (! Schema::hasTable('operational_incidents')) {
            Schema::create('operational_incidents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('incident_number')->unique();
                $table->string('incident_type');
                $table->string('severity');
                $table->string('priority');
                $table->string('status')->default('open');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('source_label')->nullable();
                $table->string('source_url')->nullable();
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('first_response_at')->nullable();
                $table->timestamp('response_due_at')->nullable();
                $table->timestamp('resolution_due_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->string('sla_status')->nullable();
                $table->string('root_cause_category')->nullable();
                $table->text('root_cause_summary')->nullable();
                $table->text('resolution_note')->nullable();
                $table->text('prevention_notes')->nullable();
                $table->boolean('corrective_action_required')->default(false);
                $table->text('no_action_required_reason')->nullable();
                $table->unsignedInteger('occurrence_count')->default(1);
                $table->timestamp('last_seen_at')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->index('company_id');
                $table->index('incident_number');
                $table->index('incident_type');
                $table->index('severity');
                $table->index('priority');
                $table->index('status');
                $table->index('source_type');
                $table->index('source_id');
                $table->index('assigned_user_id');
                $table->index('response_due_at');
                $table->index('resolution_due_at');
                $table->index('sla_status');
                $table->index('root_cause_category');
                $table->index('last_seen_at');
            });
        }

        if (! Schema::hasTable('operational_incident_events')) {
            Schema::create('operational_incident_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('operational_incident_id')->constrained()->cascadeOnDelete();
                $table->string('event_type');
                $table->json('old_values_json')->nullable();
                $table->json('new_values_json')->nullable();
                $table->json('metadata_json')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->nullable();

                $table->index('operational_incident_id');
                $table->index('event_type');
                $table->index('created_by_user_id');
                $table->index('created_at');
            });
        }

        if (! Schema::hasTable('operational_incident_comments')) {
            Schema::create('operational_incident_comments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('operational_incident_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->text('comment');
                $table->boolean('is_internal')->default(true);
                $table->json('metadata_json')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('incident_corrective_actions')) {
            Schema::create('incident_corrective_actions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('operational_incident_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('due_date')->nullable();
                $table->string('status')->default('open');
                $table->text('completion_note')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index('operational_incident_id');
                $table->index('owner_user_id');
                $table->index('due_date');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('incident_sla_policies')) {
            Schema::create('incident_sla_policies', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('incident_type')->nullable();
                $table->string('severity')->nullable();
                $table->string('priority')->nullable();
                $table->unsignedInteger('response_minutes');
                $table->unsignedInteger('resolution_minutes');
                $table->unsignedInteger('escalation_minutes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('company_id');
                $table->index('incident_type');
                $table->index('severity');
                $table->index('priority');
                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('incident_escalations')) {
            Schema::create('incident_escalations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('operational_incident_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('escalation_level')->default(1);
                $table->foreignId('escalated_to_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('escalated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason');
                $table->string('status')->default('open');
                $table->timestamp('escalated_at');
                $table->timestamp('resolved_at')->nullable();
                $table->json('metadata_json')->nullable();
                $table->timestamps();

                $table->index('operational_incident_id');
                $table->index('escalation_level');
                $table->index('escalated_to_user_id');
                $table->index('status');
                $table->index('escalated_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incident_escalations');
        Schema::dropIfExists('incident_sla_policies');
        Schema::dropIfExists('incident_corrective_actions');
        Schema::dropIfExists('operational_incident_comments');
        Schema::dropIfExists('operational_incident_events');
        Schema::dropIfExists('operational_incidents');
    }
};
