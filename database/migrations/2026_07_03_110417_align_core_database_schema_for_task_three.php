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
        Schema::table('inbound_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('inbound_orders', 'supplier_order_id')) {
                $table->foreignId('supplier_order_id')->nullable()->after('supplier_id')->constrained('supplier_orders')->nullOnDelete();
            }
        });

        Schema::table('inbound_order_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('inbound_order_items', 'damaged_quantity')) {
                $table->decimal('damaged_quantity', 18, 4)->nullable()->after('received_quantity');
            }

            if (! Schema::hasColumn('inbound_order_items', 'receiving_notes')) {
                $table->text('receiving_notes')->nullable()->after('damaged_quantity');
            }
        });

        Schema::table('supplier_order_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('supplier_order_items', 'damaged_quantity')) {
                $table->decimal('damaged_quantity', 18, 4)->nullable()->after('received_quantity');
            }

            if (! Schema::hasColumn('supplier_order_items', 'receiving_notes')) {
                $table->text('receiving_notes')->nullable()->after('damaged_quantity');
            }
        });

        Schema::table('supplier_confirmations', function (Blueprint $table): void {
            if (! Schema::hasColumn('supplier_confirmations', 'source_type')) {
                $table->string('source_type')->nullable()->after('created_from_form_autofill_run_id');
            }

            if (! Schema::hasColumn('supplier_confirmations', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }

            if (! Schema::hasColumn('supplier_confirmations', 'output_json')) {
                $table->json('output_json')->nullable()->after('source_id');
            }

            if (! Schema::hasColumn('supplier_confirmations', 'discrepancies_json')) {
                $table->json('discrepancies_json')->nullable()->after('output_json');
            }

            if (! Schema::hasColumn('supplier_confirmations', 'applied_by_user_id')) {
                $table->foreignId('applied_by_user_id')->nullable()->after('discrepancies_json')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('supplier_confirmations', 'applied_at')) {
                $table->timestamp('applied_at')->nullable()->after('applied_by_user_id');
            }

            $table->index(['source_type', 'source_id']);
        });

        Schema::table('supplier_confirmation_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('supplier_confirmation_items', 'source_item_json')) {
                $table->json('source_item_json')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('supplier_confirmation_items', 'matched_by')) {
                $table->string('matched_by')->nullable()->after('source_item_json');
            }

            if (! Schema::hasColumn('supplier_confirmation_items', 'discrepancy_type')) {
                $table->string('discrepancy_type')->nullable()->after('matched_by');
            }

            if (! Schema::hasColumn('supplier_confirmation_items', 'discrepancies_json')) {
                $table->json('discrepancies_json')->nullable()->after('discrepancy_type');
            }
        });

        Schema::table('carrier_quotes', function (Blueprint $table): void {
            if (! Schema::hasColumn('carrier_quotes', 'source_type')) {
                $table->string('source_type')->nullable()->after('created_from_form_autofill_run_id');
            }

            if (! Schema::hasColumn('carrier_quotes', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }

            if (! Schema::hasColumn('carrier_quotes', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('source_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('carrier_quotes', 'selected_by_user_id')) {
                $table->foreignId('selected_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('carrier_quotes', 'selected_at')) {
                $table->timestamp('selected_at')->nullable()->after('selected_by_user_id');
            }

            if (! Schema::hasColumn('carrier_quotes', 'rejected_by_user_id')) {
                $table->foreignId('rejected_by_user_id')->nullable()->after('selected_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('carrier_quotes', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by_user_id');
            }

            if (! Schema::hasColumn('carrier_quotes', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }

            if (! Schema::hasColumn('carrier_quotes', 'validation_errors_json')) {
                $table->json('validation_errors_json')->nullable()->after('rejection_reason');
            }

            if (! Schema::hasColumn('carrier_quotes', 'warnings_json')) {
                $table->json('warnings_json')->nullable()->after('validation_errors_json');
            }

            $table->index(['source_type', 'source_id']);
        });

        Schema::table('logistics_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('logistics_records', 'supplier_confirmation_id')) {
                $table->foreignId('supplier_confirmation_id')->nullable()->after('carrier_id')->constrained('supplier_confirmations')->nullOnDelete();
            }

            if (! Schema::hasColumn('logistics_records', 'selected_carrier_quote_id')) {
                $table->foreignId('selected_carrier_quote_id')->nullable()->after('supplier_confirmation_id')->constrained('carrier_quotes')->nullOnDelete();
            }

            if (! Schema::hasColumn('logistics_records', 'receiving_discrepancies_json')) {
                $table->json('receiving_discrepancies_json')->nullable()->after('external_sheet_reference');
            }

            if (! Schema::hasColumn('logistics_records', 'received_by_user_id')) {
                $table->foreignId('received_by_user_id')->nullable()->after('receiving_discrepancies_json')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('logistics_records', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('received_by_user_id');
            }

            if (! Schema::hasColumn('logistics_records', 'last_delay_checked_at')) {
                $table->timestamp('last_delay_checked_at')->nullable()->after('received_at');
            }

            if (! Schema::hasColumn('logistics_records', 'delay_reason')) {
                $table->text('delay_reason')->nullable()->after('last_delay_checked_at');
            }
        });

        Schema::table('integration_connections', function (Blueprint $table): void {
            if (! Schema::hasColumn('integration_connections', 'provider')) {
                $table->string('provider')->nullable()->after('name');
            }

            if (! Schema::hasColumn('integration_connections', 'environment')) {
                $table->string('environment')->nullable()->after('provider');
            }

            if (! Schema::hasColumn('integration_connections', 'is_external')) {
                $table->boolean('is_external')->default(false)->after('encrypted_config');
            }

            if (! Schema::hasColumn('integration_connections', 'requires_approval')) {
                $table->boolean('requires_approval')->default(true)->after('is_external');
            }

            if (! Schema::hasColumn('integration_connections', 'status')) {
                $table->string('status')->default('draft')->after('requires_approval');
            }

            if (! Schema::hasColumn('integration_connections', 'approval_status')) {
                $table->string('approval_status')->nullable()->after('status');
            }

            if (! Schema::hasColumn('integration_connections', 'approved_by_user_id')) {
                $table->foreignId('approved_by_user_id')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('integration_connections', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            }

            if (! Schema::hasColumn('integration_connections', 'last_tested_at')) {
                $table->timestamp('last_tested_at')->nullable()->after('approved_at');
            }

            if (! Schema::hasColumn('integration_connections', 'last_test_status')) {
                $table->string('last_test_status')->nullable()->after('last_tested_at');
            }

            if (! Schema::hasColumn('integration_connections', 'last_test_result_json')) {
                $table->json('last_test_result_json')->nullable()->after('last_test_status');
            }

            if (! Schema::hasColumn('integration_connections', 'notes')) {
                $table->text('notes')->nullable()->after('last_sync_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('integration_connections', function (Blueprint $table): void {
            if (Schema::hasColumn('integration_connections', 'approved_by_user_id')) {
                $table->dropConstrainedForeignId('approved_by_user_id');
            }

            $table->dropColumn([
                'provider',
                'environment',
                'is_external',
                'requires_approval',
                'status',
                'approval_status',
                'approved_at',
                'last_tested_at',
                'last_test_status',
                'last_test_result_json',
                'notes',
            ]);
        });

        Schema::table('logistics_records', function (Blueprint $table): void {
            if (Schema::hasColumn('logistics_records', 'received_by_user_id')) {
                $table->dropConstrainedForeignId('received_by_user_id');
            }

            if (Schema::hasColumn('logistics_records', 'selected_carrier_quote_id')) {
                $table->dropConstrainedForeignId('selected_carrier_quote_id');
            }

            if (Schema::hasColumn('logistics_records', 'supplier_confirmation_id')) {
                $table->dropConstrainedForeignId('supplier_confirmation_id');
            }

            $table->dropColumn([
                'receiving_discrepancies_json',
                'received_at',
                'last_delay_checked_at',
                'delay_reason',
            ]);
        });

        Schema::table('carrier_quotes', function (Blueprint $table): void {
            $table->dropIndex('carrier_quotes_source_type_source_id_index');

            if (Schema::hasColumn('carrier_quotes', 'rejected_by_user_id')) {
                $table->dropConstrainedForeignId('rejected_by_user_id');
            }

            if (Schema::hasColumn('carrier_quotes', 'selected_by_user_id')) {
                $table->dropConstrainedForeignId('selected_by_user_id');
            }

            if (Schema::hasColumn('carrier_quotes', 'created_by_user_id')) {
                $table->dropConstrainedForeignId('created_by_user_id');
            }

            $table->dropColumn([
                'source_type',
                'source_id',
                'selected_at',
                'rejected_at',
                'rejection_reason',
                'validation_errors_json',
                'warnings_json',
            ]);
        });

        Schema::table('supplier_confirmation_items', function (Blueprint $table): void {
            $table->dropColumn([
                'source_item_json',
                'matched_by',
                'discrepancy_type',
                'discrepancies_json',
            ]);
        });

        Schema::table('supplier_confirmations', function (Blueprint $table): void {
            $table->dropIndex('supplier_confirmations_source_type_source_id_index');

            if (Schema::hasColumn('supplier_confirmations', 'applied_by_user_id')) {
                $table->dropConstrainedForeignId('applied_by_user_id');
            }

            $table->dropColumn([
                'source_type',
                'source_id',
                'output_json',
                'discrepancies_json',
                'applied_at',
            ]);
        });

        Schema::table('supplier_order_items', function (Blueprint $table): void {
            $table->dropColumn([
                'damaged_quantity',
                'receiving_notes',
            ]);
        });

        Schema::table('inbound_order_items', function (Blueprint $table): void {
            $table->dropColumn([
                'damaged_quantity',
                'receiving_notes',
            ]);
        });

        Schema::table('inbound_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('inbound_orders', 'supplier_order_id')) {
                $table->dropConstrainedForeignId('supplier_order_id');
            }
        });
    }
};
