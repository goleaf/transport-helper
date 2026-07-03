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
        Schema::table('roles', function (Blueprint $table): void {
            if (! Schema::hasColumn('roles', 'label')) {
                $table->string('label')->nullable();
            }
        });

        Schema::table('permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('permissions', 'label')) {
                $table->string('label')->nullable();
            }
        });

        Schema::table('supplier_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('supplier_orders', 'email_subject')) {
                $table->string('email_subject')->nullable();
            }

            if (! Schema::hasColumn('supplier_orders', 'email_body')) {
                $table->longText('email_body')->nullable();
            }

            if (! Schema::hasColumn('supplier_orders', 'email_approved_at')) {
                $table->timestamp('email_approved_at')->nullable();
            }

            if (! Schema::hasColumn('supplier_orders', 'email_approved_by_user_id')) {
                $table->foreignId('email_approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('supplier_orders', 'no_attachment_confirmed')) {
                $table->boolean('no_attachment_confirmed')->default(false);
            }
        });

        Schema::table('form_templates', function (Blueprint $table): void {
            $table->unique(['company_id', 'code', 'version'], 'form_templates_company_code_version_unique');
        });

        Schema::table('supplier_confirmations', function (Blueprint $table): void {
            $table->foreign('created_from_form_autofill_run_id', 'supplier_confirmations_form_autofill_run_fk')
                ->references('id')
                ->on('form_autofill_runs')
                ->nullOnDelete();
        });

        Schema::table('carrier_quotes', function (Blueprint $table): void {
            $table->foreign('created_from_form_autofill_run_id', 'carrier_quotes_form_autofill_run_fk')
                ->references('id')
                ->on('form_autofill_runs')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carrier_quotes', function (Blueprint $table): void {
            $table->dropForeign('carrier_quotes_form_autofill_run_fk');
        });

        Schema::table('supplier_confirmations', function (Blueprint $table): void {
            $table->dropForeign('supplier_confirmations_form_autofill_run_fk');
        });

        Schema::table('form_templates', function (Blueprint $table): void {
            $table->dropUnique('form_templates_company_code_version_unique');
        });

        Schema::table('supplier_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('supplier_orders', 'email_approved_by_user_id')) {
                $table->dropConstrainedForeignId('email_approved_by_user_id');
            }

            $table->dropColumn([
                'email_subject',
                'email_body',
                'email_approved_at',
                'no_attachment_confirmed',
            ]);
        });

        Schema::table('permissions', function (Blueprint $table): void {
            if (Schema::hasColumn('permissions', 'label')) {
                $table->dropColumn('label');
            }
        });

        Schema::table('roles', function (Blueprint $table): void {
            if (Schema::hasColumn('roles', 'label')) {
                $table->dropColumn('label');
            }
        });
    }
};
