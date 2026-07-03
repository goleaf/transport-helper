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
        Schema::create('replenishment_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('name');
            $table->string('status')->default('active');
            $table->unsignedInteger('priority')->default(100);
            $table->unsignedInteger('lead_time_days_override')->nullable();
            $table->unsignedInteger('safety_days_override')->nullable();
            $table->decimal('safety_stock_multiplier', 10, 4)->nullable();
            $table->boolean('seasonality_enabled')->default(false);
            $table->string('seasonality_mode')->nullable();
            $table->boolean('exclude_promotions')->default(true);
            $table->boolean('exclude_anomalies')->default(true);
            $table->boolean('outlier_detection_enabled')->default(false);
            $table->decimal('outlier_multiplier', 10, 4)->nullable();
            $table->string('reservation_strategy')->nullable();
            $table->string('pallet_strategy')->nullable();
            $table->string('transport_strategy')->nullable();
            $table->boolean('strategic_minimum_order_enabled')->default(false);
            $table->json('config_json')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index('category');
            $table->index('status');
            $table->index('priority');
            $table->index('is_active');
        });

        Schema::create('sales_exclusion_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->string('rule_type');
            $table->date('date_from');
            $table->date('date_to');
            $table->string('applies_to')->default('all_calculation_periods');
            $table->text('reason');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index('category');
            $table->index('rule_type');
            $table->index('date_from');
            $table->index('date_to');
            $table->index('applies_to');
            $table->index('is_active');
        });

        Schema::create('trend_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->nullable();
            $table->decimal('trend_value', 18, 6);
            $table->date('date_from');
            $table->date('date_to');
            $table->string('status')->default('draft');
            $table->text('reason');
            $table->text('approval_note')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('revoked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('revoked_at')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('product_id');
            $table->index('category');
            $table->index('status');
            $table->index('date_from');
            $table->index('date_to');
            $table->index('created_by_user_id');
            $table->index('approved_by_user_id');
        });

        Schema::create('calculation_scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('base_calculation_run_id')->nullable()->constrained('calculation_runs')->nullOnDelete();
            $table->string('name');
            $table->string('status')->default('draft');
            $table->string('simulation_mode')->default('supplier');
            $table->string('formula_version')->default('v1_scenario');
            $table->json('parameters_json')->nullable();
            $table->json('profile_snapshot_json')->nullable();
            $table->json('summary_json')->nullable();
            $table->json('warnings_json')->nullable();
            $table->json('errors_json')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('simulated_at')->nullable();
            $table->foreignId('converted_order_proposal_id')->nullable()->constrained('order_proposals')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('base_calculation_run_id');
            $table->index('status');
            $table->index('simulation_mode');
            $table->index('created_by_user_id');
            $table->index('simulated_at');
            $table->index('converted_order_proposal_id');
        });

        Schema::create('calculation_scenario_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_scenario_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('base_order_proposal_item_id')->nullable()->constrained('order_proposal_items')->nullOnDelete();
            $table->string('status')->default('simulated');
            $table->decimal('base_raw_need', 18, 4)->nullable();
            $table->decimal('base_recommended_quantity', 18, 4)->nullable();
            $table->decimal('simulated_raw_need', 18, 4)->nullable();
            $table->decimal('simulated_recommended_quantity', 18, 4)->nullable();
            $table->decimal('difference_quantity', 18, 4)->nullable();
            $table->decimal('trend_used', 18, 6)->nullable();
            $table->decimal('seasonality_factor', 18, 6)->nullable();
            $table->foreignId('manual_trend_override_id')->nullable()->constrained('trend_overrides')->nullOnDelete();
            $table->foreignId('applied_profile_id')->nullable()->constrained('replenishment_profiles')->nullOnDelete();
            $table->json('input_json')->nullable();
            $table->json('output_json')->nullable();
            $table->json('explanation_json')->nullable();
            $table->json('warnings_json')->nullable();
            $table->boolean('requires_human_review')->default(false);
            $table->timestamps();

            $table->index('calculation_scenario_id');
            $table->index('product_id');
            $table->index('status');
            $table->index('requires_human_review');
            $table->index('manual_trend_override_id');
            $table->index('applied_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculation_scenario_items');
        Schema::dropIfExists('calculation_scenarios');
        Schema::dropIfExists('trend_overrides');
        Schema::dropIfExists('sales_exclusion_rules');
        Schema::dropIfExists('replenishment_profiles');
    }
};
