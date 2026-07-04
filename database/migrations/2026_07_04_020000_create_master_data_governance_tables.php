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
        Schema::create('product_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->string('alias_type')->default('sku_alias');
            $table->string('source_type')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('status')->default('active');
            $table->decimal('confidence', 5, 4)->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'alias', 'alias_type']);
            $table->index('company_id');
            $table->index('product_id');
            $table->index('alias');
            $table->index('alias_type');
            $table->index('status');
            $table->index('source_type');
        });

        Schema::create('supplier_aliases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('alias');
            $table->string('alias_type')->default('name_alias');
            $table->string('source_type')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('status')->default('active');
            $table->decimal('confidence', 5, 4)->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'alias', 'alias_type']);
            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('alias');
            $table->index('alias_type');
            $table->index('status');
            $table->index('source_type');
        });

        Schema::create('supplier_product_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_sku')->nullable();
            $table->string('manufacturer_sku')->nullable();
            $table->string('supplier_product_name')->nullable();
            $table->string('barcode')->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('status')->default('active');
            $table->decimal('confidence', 5, 4)->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index('supplier_sku');
            $table->index('manufacturer_sku');
            $table->index('barcode');
            $table->index('status');
        });

        Schema::create('master_data_change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('request_type');
            $table->string('status')->default('draft');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('applied_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('related_model');
            $table->json('requested_changes_json')->nullable();
            $table->text('reason');
            $table->text('approval_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('request_type');
            $table->index('status');
            $table->index('requested_by_user_id');
            $table->index('approved_by_user_id');
        });

        Schema::create('master_data_merge_proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('merge_type');
            $table->string('source_model_type');
            $table->unsignedBigInteger('source_model_id');
            $table->string('target_model_type');
            $table->unsignedBigInteger('target_model_id');
            $table->string('status')->default('draft');
            $table->text('reason');
            $table->json('impact_json')->nullable();
            $table->foreignId('proposed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('executed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('execution_result_json')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('merge_type');
            $table->index('status');
            $table->index(['source_model_type', 'source_model_id']);
            $table->index(['target_model_type', 'target_model_id']);
        });

        Schema::create('unknown_sku_resolutions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unknown_sku');
            $table->string('source_type')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('status')->default('unresolved');
            $table->foreignId('resolved_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('resolution_type')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata_json')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('unknown_sku');
            $table->index('source_type');
            $table->index('source_reference');
            $table->index('status');
            $table->index('resolved_product_id');
        });

        Schema::create('data_steward_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stewardship_type');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('user_id');
            $table->index('stewardship_type');
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index('category');
            $table->index('is_active');
        });

        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'lifecycle_status')) {
                $table->string('lifecycle_status')->nullable()->after('is_active')->index();
            }

            if (! Schema::hasColumn('products', 'lifecycle_reason')) {
                $table->text('lifecycle_reason')->nullable()->after('lifecycle_status');
            }

            if (! Schema::hasColumn('products', 'replaced_by_product_id')) {
                $table->foreignId('replaced_by_product_id')->nullable()->after('lifecycle_reason')->constrained('products')->nullOnDelete();
            }

            if (! Schema::hasColumn('products', 'merged_into_product_id')) {
                $table->foreignId('merged_into_product_id')->nullable()->after('replaced_by_product_id')->constrained('products')->nullOnDelete();
            }
        });

        Schema::table('suppliers', function (Blueprint $table): void {
            if (! Schema::hasColumn('suppliers', 'lifecycle_status')) {
                $table->string('lifecycle_status')->nullable()->after('is_active')->index();
            }

            if (! Schema::hasColumn('suppliers', 'lifecycle_reason')) {
                $table->text('lifecycle_reason')->nullable()->after('lifecycle_status');
            }

            if (! Schema::hasColumn('suppliers', 'merged_into_supplier_id')) {
                $table->foreignId('merged_into_supplier_id')->nullable()->after('lifecycle_reason')->constrained('suppliers')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            if (Schema::hasColumn('suppliers', 'merged_into_supplier_id')) {
                $table->dropConstrainedForeignId('merged_into_supplier_id');
            }

            if (Schema::hasColumn('suppliers', 'lifecycle_status')) {
                $table->dropColumn(['lifecycle_status', 'lifecycle_reason']);
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'merged_into_product_id')) {
                $table->dropConstrainedForeignId('merged_into_product_id');
            }

            if (Schema::hasColumn('products', 'replaced_by_product_id')) {
                $table->dropConstrainedForeignId('replaced_by_product_id');
            }

            if (Schema::hasColumn('products', 'lifecycle_status')) {
                $table->dropColumn(['lifecycle_status', 'lifecycle_reason']);
            }
        });

        Schema::dropIfExists('data_steward_assignments');
        Schema::dropIfExists('unknown_sku_resolutions');
        Schema::dropIfExists('master_data_merge_proposals');
        Schema::dropIfExists('master_data_change_requests');
        Schema::dropIfExists('supplier_product_identities');
        Schema::dropIfExists('supplier_aliases');
        Schema::dropIfExists('product_aliases');
    }
};
