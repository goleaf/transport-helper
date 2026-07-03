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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('timezone');
            $table->string('default_currency');
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type');
            $table->string('default_language')->nullable();
            $table->string('default_currency')->nullable();
            $table->integer('default_lead_time_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->boolean('receives_orders')->default(false);
            $table->boolean('receives_transport_requests')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('sku');
            $table->string('manufacturer_sku')->nullable();
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('unit')->default('pcs');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'sku']);
        });

        Schema::create('supplier_product_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_sku')->nullable();
            $table->decimal('moq', 14, 3)->nullable();
            $table->decimal('pack_multiple', 14, 3)->nullable();
            $table->decimal('pallet_quantity', 14, 3)->nullable();
            $table->decimal('min_transport_quantity', 14, 3)->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->integer('safety_days')->nullable();
            $table->string('safety_rule_type')->nullable();
            $table->string('transport_rule_type')->nullable();
            $table->boolean('order_enabled')->default(true);
            $table->timestamps();
            $table->unique(['supplier_id', 'product_id']);
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('source_type');
            $table->string('source_name')->nullable();
            $table->string('adapter');
            $table->string('original_filename')->nullable();
            $table->string('checksum')->nullable();
            $table->string('status');
            $table->integer('total_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_summary')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('free_stock', 14, 3);
            $table->decimal('total_stock', 14, 3)->nullable();
            $table->decimal('reserved_quantity', 14, 3)->nullable();
            $table->decimal('damaged_quantity', 14, 3)->nullable();
            $table->decimal('inactive_quantity', 14, 3)->nullable();
            $table->decimal('in_transit_quantity', 14, 3)->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_reference')->nullable();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['product_id', 'snapshot_date']);
        });

        Schema::create('sales_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('sales_date');
            $table->decimal('quantity', 14, 3);
            $table->string('channel')->nullable();
            $table->string('customer_id')->nullable();
            $table->boolean('is_promotion')->default(false);
            $table->boolean('is_anomaly')->default(false);
            $table->string('anomaly_reason')->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_reference')->nullable();
            $table->foreignId('import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['product_id', 'sales_date']);
        });

        Schema::create('inbound_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->nullable();
            $table->string('supplier_order_reference')->nullable();
            $table->string('status');
            $table->timestamp('ordered_at')->nullable();
            $table->date('expected_arrival_date')->nullable();
            $table->date('confirmed_arrival_date')->nullable();
            $table->date('ready_date')->nullable();
            $table->date('shipped_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inbound_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('ordered_quantity', 14, 3);
            $table->decimal('confirmed_quantity', 14, 3)->nullable();
            $table->decimal('received_quantity', 14, 3)->nullable();
            $table->date('expected_arrival_date')->nullable();
            $table->date('confirmed_arrival_date')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('project_name')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('manager_name')->nullable();
            $table->date('reserved_at');
            $table->date('expected_usage_date')->nullable();
            $table->string('status');
            $table->string('source_type')->nullable();
            $table->string('source_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('calculation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('calculation_date');
            $table->string('formula_version');
            $table->json('parameters_json')->nullable();
            $table->string('status');
            $table->foreignId('started_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('calculation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->integer('total_lines')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_proposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->date('t0_date');
            $table->date('t1_date');
            $table->date('t2_date');
            $table->date('t3_date');
            $table->decimal('trend', 14, 3)->nullable();
            $table->decimal('need_t0_t1', 14, 3)->nullable();
            $table->decimal('stock_t1', 14, 3)->nullable();
            $table->decimal('need_t1_t2', 14, 3)->nullable();
            $table->decimal('safety_stock', 14, 3)->nullable();
            $table->decimal('inbound_until_t1', 14, 3)->nullable();
            $table->decimal('inbound_t1_t3', 14, 3)->nullable();
            $table->decimal('reserved_quantity', 14, 3)->nullable();
            $table->decimal('raw_need', 14, 3)->nullable();
            $table->decimal('moq_applied', 14, 3)->nullable();
            $table->decimal('pack_multiple_applied', 14, 3)->nullable();
            $table->decimal('pallet_quantity_applied', 14, 3)->nullable();
            $table->decimal('recommended_quantity', 14, 3)->nullable();
            $table->decimal('approved_quantity', 14, 3)->nullable();
            $table->decimal('user_adjusted_quantity', 14, 3)->nullable();
            $table->text('adjustment_reason')->nullable();
            $table->json('explanation_json')->nullable();
            $table->json('warnings_json')->nullable();
            $table->boolean('requires_human_review')->default(false);
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('supplier_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number');
            $table->string('status');
            $table->date('order_date')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->string('email_message_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('ordered_quantity', 14, 3);
            $table->decimal('confirmed_quantity', 14, 3)->nullable();
            $table->decimal('received_quantity', 14, 3)->nullable();
            $table->decimal('unit_price', 14, 3)->nullable();
            $table->string('currency')->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('provider');
            $table->string('email_address');
            $table->json('encrypted_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction');
            $table->string('message_id')->nullable();
            $table->string('thread_id')->nullable();
            $table->string('from_email')->nullable();
            $table->json('to_json')->nullable();
            $table->json('cc_json')->nullable();
            $table->string('subject')->nullable();
            $table->text('body_text')->nullable();
            $table->text('body_html')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->foreignId('related_supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('related_supplier_order_id')->nullable()->constrained('supplier_orders')->nullOnDelete();
            $table->string('status');
            $table->json('raw_headers_json')->nullable();
            $table->timestamps();
        });

        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_message_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_email_extractions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_message_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->string('prompt_version');
            $table->string('input_hash')->nullable();
            $table->json('output_json')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->boolean('requires_human_review')->default(true);
            $table->string('review_reason')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('supplier_reference')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('ready_date')->nullable();
            $table->date('shipping_date')->nullable();
            $table->date('expected_arrival_date')->nullable();
            $table->string('status');
            $table->text('discrepancy_summary')->nullable();
            $table->foreignId('created_from_ai_extraction_id')->nullable()->constrained('ai_email_extractions')->nullOnDelete();
            $table->unsignedBigInteger('created_from_form_autofill_run_id')->nullable();
            $table->timestamps();
        });

        Schema::create('supplier_confirmation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_confirmation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('ordered_quantity', 14, 3);
            $table->decimal('confirmed_quantity', 14, 3);
            $table->decimal('discrepancy_quantity', 14, 3)->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('default_currency')->nullable();
            $table->decimal('reliability_score', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('carrier_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrier_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('carrier_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('email_message_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 14, 3)->nullable();
            $table->string('currency')->nullable();
            $table->date('pickup_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->integer('transit_days')->nullable();
            $table->text('conditions')->nullable();
            $table->decimal('reliability_score', 5, 2)->nullable();
            $table->decimal('calculated_score', 8, 3)->nullable();
            $table->json('score_explanation_json')->nullable();
            $table->string('status');
            $table->foreignId('created_from_ai_extraction_id')->nullable()->constrained('ai_email_extractions')->nullOnDelete();
            $table->unsignedBigInteger('created_from_form_autofill_run_id')->nullable();
            $table->timestamps();
        });

        Schema::create('logistics_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->date('order_date')->nullable();
            $table->date('confirmation_date')->nullable();
            $table->date('ready_date')->nullable();
            $table->date('pickup_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->date('actual_received_date')->nullable();
            $table->decimal('transport_price', 14, 3)->nullable();
            $table->string('currency')->nullable();
            $table->string('status');
            $table->string('external_sheet_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->json('raw_json')->nullable();
            $table->json('normalized_json')->nullable();
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->nullableMorphs('related_model');
            $table->timestamps();
        });

        Schema::create('export_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('export_type');
            $table->nullableMorphs('related_model');
            $table->string('filename');
            $table->string('stored_path');
            $table->string('mime_type')->nullable();
            $table->string('status');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('integration_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('name');
            $table->json('encrypted_config')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value_json')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'key']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->nullableMorphs('auditable');
            $table->json('old_values_json')->nullable();
            $table->json('new_values_json')->nullable();
            $table->json('metadata_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('integration_connections');
        Schema::dropIfExists('export_files');
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('logistics_records');
        Schema::dropIfExists('carrier_quotes');
        Schema::dropIfExists('carrier_contacts');
        Schema::dropIfExists('carriers');
        Schema::dropIfExists('supplier_confirmation_items');
        Schema::dropIfExists('supplier_confirmations');
        Schema::dropIfExists('ai_email_extractions');
        Schema::dropIfExists('email_attachments');
        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('email_accounts');
        Schema::dropIfExists('supplier_order_items');
        Schema::dropIfExists('supplier_orders');
        Schema::dropIfExists('order_proposal_items');
        Schema::dropIfExists('order_proposals');
        Schema::dropIfExists('calculation_runs');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('inbound_order_items');
        Schema::dropIfExists('inbound_orders');
        Schema::dropIfExists('sales_history');
        Schema::dropIfExists('stock_snapshots');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('supplier_product_rules');
        Schema::dropIfExists('products');
        Schema::dropIfExists('supplier_contacts');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
