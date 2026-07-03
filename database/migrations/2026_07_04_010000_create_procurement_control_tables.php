<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_product_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('currency');
            $table->decimal('unit_price', 18, 4);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_reference')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index('currency');
            $table->index('valid_from');
            $table->index('valid_to');
            $table->index('status');
        });

        Schema::create('procurement_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('status')->default('active');
            $table->string('enforcement_mode')->default('advisory');
            $table->string('default_currency')->default('EUR');
            $table->json('rules_json')->nullable();
            $table->json('approval_thresholds_json')->nullable();
            $table->json('supplier_rules_json')->nullable();
            $table->json('budget_rules_json')->nullable();
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
            $table->index('enforcement_mode');
            $table->index('is_default');
        });

        Schema::create('procurement_budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('period_type');
            $table->date('date_from');
            $table->date('date_to');
            $table->string('currency')->default('EUR');
            $table->decimal('total_amount', 18, 4);
            $table->string('status')->default('draft');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('period_type');
            $table->index('date_from');
            $table->index('date_to');
            $table->index('currency');
            $table->index('status');
            $table->index('owner_user_id');
        });

        Schema::create('procurement_budget_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('project_name')->nullable();
            $table->string('manager_name')->nullable();
            $table->decimal('amount', 18, 4);
            $table->decimal('committed_amount', 18, 4)->nullable();
            $table->decimal('spent_amount', 18, 4)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index('procurement_budget_id');
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index('category');
            $table->index('project_name');
            $table->index('manager_name');
        });

        Schema::create('procurement_approval_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->string('status')->default('pending');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('required_role')->nullable();
            $table->string('required_permission')->nullable();
            $table->decimal('amount', 18, 4)->nullable();
            $table->string('currency')->nullable();
            $table->text('reason');
            $table->json('metadata_json')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index(['approvable_type', 'approvable_id']);
            $table->index('status');
            $table->index('requested_by_user_id');
            $table->index('required_role');
            $table->index('required_permission');
            $table->index('expires_at');
        });

        Schema::create('procurement_approval_decisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('procurement_approval_request_id')->constrained()->cascadeOnDelete();
            $table->string('decision');
            $table->foreignId('decision_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->json('metadata_json')->nullable();
            $table->dateTime('decided_at');
            $table->timestamps();

            $table->index('procurement_approval_request_id');
            $table->index('decision');
            $table->index('decision_by_user_id');
            $table->index('decided_at');
        });

        Schema::create('procurement_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('exception_type');
            $table->string('exceptable_type')->nullable();
            $table->unsignedBigInteger('exceptable_id')->nullable();
            $table->string('status')->default('pending');
            $table->text('reason');
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('rejected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('exception_type');
            $table->index(['exceptable_type', 'exceptable_id']);
            $table->index('status');
            $table->index('requested_by_user_id');
            $table->index('approved_by_user_id');
            $table->index('rejected_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_exceptions');
        Schema::dropIfExists('procurement_approval_decisions');
        Schema::dropIfExists('procurement_approval_requests');
        Schema::dropIfExists('procurement_budget_lines');
        Schema::dropIfExists('procurement_budgets');
        Schema::dropIfExists('procurement_policies');
        Schema::dropIfExists('supplier_product_prices');
    }
};
