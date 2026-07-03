<?php

namespace App\Providers;

use App\Contracts\AI\AiEmailAnalyzerInterface;
use App\Contracts\AI\AiEmailFormExtractorInterface;
use App\Contracts\AI\AiEmailReplyDraftGeneratorInterface;
use App\Contracts\Email\EmailSenderInterface;
use App\Services\AI\Email\RuleBasedAiEmailAnalyzer;
use App\Services\AI\Forms\RuleBasedAiEmailFormExtractor;
use App\Services\AI\NullAiEmailReplyDraftGenerator;
use App\Services\Email\Senders\LogEmailSender;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AiEmailAnalyzerInterface::class, RuleBasedAiEmailAnalyzer::class);
        $this->app->bind(AiEmailReplyDraftGeneratorInterface::class, NullAiEmailReplyDraftGenerator::class);
        $this->app->bind(AiEmailFormExtractorInterface::class, RuleBasedAiEmailFormExtractor::class);
        $this->app->bind(EmailSenderInterface::class, LogEmailSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['layouts.app', 'layouts.auth'], function ($view): void {
            $view->with('htmlLocale', str_replace('_', '-', app()->getLocale()));
        });
    }
}
