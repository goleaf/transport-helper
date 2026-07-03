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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->json('value_json')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'key']);
            $table->index('key');
        });

        Schema::create('saved_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('key');
            $table->string('route_name');
            $table->string('model_type')->nullable();
            $table->json('filters_json')->nullable();
            $table->json('columns_json')->nullable();
            $table->json('sort_json')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_shared')->default(false);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('route_name');
            $table->index('key');
            $table->index('is_default');
            $table->index('is_shared');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_views');
        Schema::dropIfExists('user_preferences');
    }
};
