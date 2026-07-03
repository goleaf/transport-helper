<?php

namespace App\Providers;

use App\Contracts\AI\AiEmailAnalyzerInterface;
use App\Contracts\AI\AiEmailFormExtractorInterface;
use App\Contracts\AI\AiEmailReplyDraftGeneratorInterface;
use App\Services\AI\NullAiEmailAnalyzer;
use App\Services\AI\NullAiEmailFormExtractor;
use App\Services\AI\NullAiEmailReplyDraftGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiEmailAnalyzerInterface::class, NullAiEmailAnalyzer::class);
        $this->app->bind(AiEmailReplyDraftGeneratorInterface::class, NullAiEmailReplyDraftGenerator::class);
        $this->app->bind(AiEmailFormExtractorInterface::class, NullAiEmailFormExtractor::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
