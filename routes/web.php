<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Supply\AiEmailExtractionController;
use App\Http\Controllers\Supply\AiEmailExtractionReviewController;
use App\Http\Controllers\Supply\AnalyticsDashboardController;
use App\Http\Controllers\Supply\AnalyticsExportController;
use App\Http\Controllers\Supply\AnalyticsReportController;
use App\Http\Controllers\Supply\AnalyzeInboundEmailController;
use App\Http\Controllers\Supply\ApplyAiCarrierQuoteController;
use App\Http\Controllers\Supply\ApplyAiSupplierConfirmationController;
use App\Http\Controllers\Supply\ApplyFormAutofillCarrierQuoteController;
use App\Http\Controllers\Supply\ApplyFormAutofillSupplierConfirmationController;
use App\Http\Controllers\Supply\AppSettingController;
use App\Http\Controllers\Supply\AuditLogController;
use App\Http\Controllers\Supply\CalculationRunController;
use App\Http\Controllers\Supply\CalculationScenarioController;
use App\Http\Controllers\Supply\CarrierController;
use App\Http\Controllers\Supply\CarrierQuoteController;
use App\Http\Controllers\Supply\CarrierQuoteRejectionController;
use App\Http\Controllers\Supply\CarrierQuoteRequestController;
use App\Http\Controllers\Supply\CarrierQuoteScoringController;
use App\Http\Controllers\Supply\CarrierSelectionController;
use App\Http\Controllers\Supply\ConvertProposalToSupplierOrderController;
use App\Http\Controllers\Supply\EmailFormAutofillController;
use App\Http\Controllers\Supply\EmailMessageController;
use App\Http\Controllers\Supply\ExportDownloadController;
use App\Http\Controllers\Supply\ExportFileController;
use App\Http\Controllers\Supply\FormAutofillApplyGateController;
use App\Http\Controllers\Supply\FormAutofillExportController;
use App\Http\Controllers\Supply\FormAutofillFieldReviewController;
use App\Http\Controllers\Supply\FormAutofillOutputDownloadController;
use App\Http\Controllers\Supply\FormAutofillRunController;
use App\Http\Controllers\Supply\FormAutofillRunValidationController;
use App\Http\Controllers\Supply\FormTemplateController;
use App\Http\Controllers\Supply\FormTemplateFieldController;
use App\Http\Controllers\Supply\GoodsReceiptController;
use App\Http\Controllers\Supply\HealthCheckController;
use App\Http\Controllers\Supply\ImportController;
use App\Http\Controllers\Supply\ImportRollbackController;
use App\Http\Controllers\Supply\InboundOrderController;
use App\Http\Controllers\Supply\IncidentAssignmentController;
use App\Http\Controllers\Supply\IncidentCommentController;
use App\Http\Controllers\Supply\IncidentCorrectiveActionController;
use App\Http\Controllers\Supply\IncidentDetectionController;
use App\Http\Controllers\Supply\IncidentExportController;
use App\Http\Controllers\Supply\IncidentReportController;
use App\Http\Controllers\Supply\IncidentRootCauseController;
use App\Http\Controllers\Supply\IncidentSlaPolicyController;
use App\Http\Controllers\Supply\IncidentStatusController;
use App\Http\Controllers\Supply\IntegrationApprovalController;
use App\Http\Controllers\Supply\IntegrationConnectionController;
use App\Http\Controllers\Supply\IntegrationTestController;
use App\Http\Controllers\Supply\LogisticsController;
use App\Http\Controllers\Supply\LogisticsExportController;
use App\Http\Controllers\Supply\LogisticsGoogleSheetsSyncController;
use App\Http\Controllers\Supply\LogisticsStatusController;
use App\Http\Controllers\Supply\ManualCarrierQuoteController;
use App\Http\Controllers\Supply\ManualInboundEmailController;
use App\Http\Controllers\Supply\ManualSupplierConfirmationController;
use App\Http\Controllers\Supply\ManufacturerFormExportController;
use App\Http\Controllers\Supply\ManufacturerFormMappingController;
use App\Http\Controllers\Supply\ManufacturerFormPreviewController;
use App\Http\Controllers\Supply\ManufacturerFormTemplateController;
use App\Http\Controllers\Supply\NotificationCenterController;
use App\Http\Controllers\Supply\NotificationReadController;
use App\Http\Controllers\Supply\OnboardingChecklistController;
use App\Http\Controllers\Supply\OperationalIncidentController;
use App\Http\Controllers\Supply\OrderProposalApprovalController;
use App\Http\Controllers\Supply\OrderProposalController;
use App\Http\Controllers\Supply\OrderProposalItemDecisionController;
use App\Http\Controllers\Supply\PilotApprovalController;
use App\Http\Controllers\Supply\PilotDryRunController;
use App\Http\Controllers\Supply\PilotFileController;
use App\Http\Controllers\Supply\PilotMappingController;
use App\Http\Controllers\Supply\PilotReadinessController;
use App\Http\Controllers\Supply\PilotReportController;
use App\Http\Controllers\Supply\PilotSupplierController;
use App\Http\Controllers\Supply\PilotUatChecklistController;
use App\Http\Controllers\Supply\ReplenishmentProfileController;
use App\Http\Controllers\Supply\ReportRunController;
use App\Http\Controllers\Supply\ReservationController;
use App\Http\Controllers\Supply\SalesExclusionRuleController;
use App\Http\Controllers\Supply\SalesHistoryController;
use App\Http\Controllers\Supply\SavedReportController;
use App\Http\Controllers\Supply\ScenarioComparisonController;
use App\Http\Controllers\Supply\ScenarioExportController;
use App\Http\Controllers\Supply\ScenarioSimulationController;
use App\Http\Controllers\Supply\StockSnapshotController;
use App\Http\Controllers\Supply\SupplierConfirmationController;
use App\Http\Controllers\Supply\SupplierController;
use App\Http\Controllers\Supply\SupplierOrderController;
use App\Http\Controllers\Supply\SupplierOrderEmailApprovalController;
use App\Http\Controllers\Supply\SupplierOrderEmailDraftController;
use App\Http\Controllers\Supply\SupplierOrderExportController;
use App\Http\Controllers\Supply\SupplierOrderSendController;
use App\Http\Controllers\Supply\SupplyDashboardController;
use App\Http\Controllers\Supply\SupplySectionController;
use App\Http\Controllers\Supply\TrendOverrideApprovalController;
use App\Http\Controllers\Supply\TrendOverrideController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'guest'])
    ->group(function (): void {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('login.store');
    });

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware(['web', 'auth'])
    ->name('logout');

Route::middleware(['web', 'auth'])
    ->prefix('supply')
    ->name('supply.')
    ->group(function (): void {
        Route::get('/', SupplyDashboardController::class)->name('dashboard');
        Route::get('dashboard', SupplyDashboardController::class)->name('dashboard.show');
        Route::get('products', [SupplySectionController::class, 'show'])->defaults('section', 'products')->name('products.index');
        Route::get('form-autofill-runs', [FormAutofillRunController::class, 'index'])->name('form-autofill-runs.index');
        Route::get('supplier-confirmations', [SupplierConfirmationController::class, 'index'])->name('supplier-confirmations.index');
        Route::get('exports', [ExportFileController::class, 'index'])->name('exports.index');
        Route::get('exports/{exportFile}', [ExportFileController::class, 'show'])->name('exports.show');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');
        Route::get('settings', [AppSettingController::class, 'index'])->name('settings.index');
        Route::get('settings/{appSetting}', [AppSettingController::class, 'show'])->name('settings.show');
        Route::get('integrations', [IntegrationConnectionController::class, 'index'])->name('integrations.index');
        Route::get('integrations/create', [IntegrationConnectionController::class, 'create'])->name('integrations.create');
        Route::post('integrations', [IntegrationConnectionController::class, 'store'])->name('integrations.store');
        Route::get('integrations/{connection}', [IntegrationConnectionController::class, 'show'])->name('integrations.show');
        Route::get('integrations/{connection}/edit', [IntegrationConnectionController::class, 'edit'])->name('integrations.edit');
        Route::match(['put', 'patch'], 'integrations/{connection}', [IntegrationConnectionController::class, 'update'])->name('integrations.update');
        Route::post('integrations/{connection}/submit-approval', [IntegrationApprovalController::class, 'submitApproval'])->name('integrations.submit-approval');
        Route::post('integrations/{connection}/approve', [IntegrationApprovalController::class, 'approve'])->name('integrations.approve');
        Route::post('integrations/{connection}/reject', [IntegrationApprovalController::class, 'reject'])->name('integrations.reject');
        Route::post('integrations/{connection}/revoke', [IntegrationApprovalController::class, 'revoke'])->name('integrations.revoke');
        Route::post('integrations/{connection}/activate', [IntegrationApprovalController::class, 'activate'])->name('integrations.activate');
        Route::post('integrations/{connection}/disable', [IntegrationApprovalController::class, 'disable'])->name('integrations.disable');
        Route::post('integrations/{connection}/test', [IntegrationTestController::class, 'store'])->name('integrations.test');
        Route::get('onboarding', [OnboardingChecklistController::class, 'index'])->name('onboarding.index');

        Route::get('analytics', AnalyticsDashboardController::class)->name('analytics.dashboard');
        Route::get('analytics/reports/{reportType}', [AnalyticsReportController::class, 'show'])->name('analytics.reports.show');
        Route::post('analytics/reports/{reportType}/run', [AnalyticsReportController::class, 'run'])->name('analytics.reports.run');
        Route::post('analytics/reports/{reportType}/export', [AnalyticsExportController::class, 'store'])->name('analytics.reports.export');
        Route::get('analytics/saved-reports', [SavedReportController::class, 'index'])->name('analytics.saved-reports.index');
        Route::post('analytics/saved-reports', [SavedReportController::class, 'store'])->name('analytics.saved-reports.store');
        Route::patch('analytics/saved-reports/{report}', [SavedReportController::class, 'update'])->name('analytics.saved-reports.update');
        Route::delete('analytics/saved-reports/{report}', [SavedReportController::class, 'destroy'])->name('analytics.saved-reports.delete');
        Route::post('analytics/saved-reports/{report}/default', [SavedReportController::class, 'setDefault'])->name('analytics.saved-reports.default');
        Route::get('analytics/report-runs', [ReportRunController::class, 'index'])->name('analytics.report-runs.index');
        Route::get('analytics/report-runs/{run}', [ReportRunController::class, 'show'])->name('analytics.report-runs.show');

        Route::get('forecasting/profiles', [ReplenishmentProfileController::class, 'index'])->name('forecasting.profiles.index');
        Route::get('forecasting/profiles/create', [ReplenishmentProfileController::class, 'create'])->name('forecasting.profiles.create');
        Route::post('forecasting/profiles', [ReplenishmentProfileController::class, 'store'])->name('forecasting.profiles.store');
        Route::get('forecasting/profiles/{profile}', [ReplenishmentProfileController::class, 'show'])->name('forecasting.profiles.show');
        Route::get('forecasting/profiles/{profile}/edit', [ReplenishmentProfileController::class, 'edit'])->name('forecasting.profiles.edit');
        Route::match(['put', 'patch'], 'forecasting/profiles/{profile}', [ReplenishmentProfileController::class, 'update'])->name('forecasting.profiles.update');
        Route::delete('forecasting/profiles/{profile}', [ReplenishmentProfileController::class, 'destroy'])->name('forecasting.profiles.archive');

        Route::get('forecasting/exclusions', [SalesExclusionRuleController::class, 'index'])->name('forecasting.exclusions.index');
        Route::get('forecasting/exclusions/create', [SalesExclusionRuleController::class, 'create'])->name('forecasting.exclusions.create');
        Route::post('forecasting/exclusions', [SalesExclusionRuleController::class, 'store'])->name('forecasting.exclusions.store');
        Route::get('forecasting/exclusions/{rule}', [SalesExclusionRuleController::class, 'show'])->name('forecasting.exclusions.show');
        Route::get('forecasting/exclusions/{rule}/edit', [SalesExclusionRuleController::class, 'edit'])->name('forecasting.exclusions.edit');
        Route::match(['put', 'patch'], 'forecasting/exclusions/{rule}', [SalesExclusionRuleController::class, 'update'])->name('forecasting.exclusions.update');

        Route::get('forecasting/overrides', [TrendOverrideController::class, 'index'])->name('forecasting.overrides.index');
        Route::get('forecasting/overrides/create', [TrendOverrideController::class, 'create'])->name('forecasting.overrides.create');
        Route::post('forecasting/overrides', [TrendOverrideController::class, 'store'])->name('forecasting.overrides.store');
        Route::get('forecasting/overrides/{override}', [TrendOverrideController::class, 'show'])->name('forecasting.overrides.show');
        Route::post('forecasting/overrides/{override}/submit', [TrendOverrideController::class, 'submit'])->name('forecasting.overrides.submit');
        Route::post('forecasting/overrides/{override}/approve', [TrendOverrideApprovalController::class, 'approve'])->name('forecasting.overrides.approve');
        Route::post('forecasting/overrides/{override}/reject', [TrendOverrideApprovalController::class, 'reject'])->name('forecasting.overrides.reject');
        Route::post('forecasting/overrides/{override}/revoke', [TrendOverrideApprovalController::class, 'revoke'])->name('forecasting.overrides.revoke');

        Route::get('forecasting/scenarios', [CalculationScenarioController::class, 'index'])->name('forecasting.scenarios.index');
        Route::get('forecasting/scenarios/create', [CalculationScenarioController::class, 'create'])->name('forecasting.scenarios.create');
        Route::post('forecasting/scenarios/simulate', [ScenarioSimulationController::class, 'store'])->name('forecasting.scenarios.simulate');
        Route::post('forecasting/scenarios/compare', [ScenarioComparisonController::class, 'store'])->name('forecasting.scenarios.compare');
        Route::get('forecasting/scenarios/{scenario}', [CalculationScenarioController::class, 'show'])->name('forecasting.scenarios.show');
        Route::post('forecasting/scenarios/{scenario}/export', [ScenarioExportController::class, 'store'])->name('forecasting.scenarios.export');

        Route::get('incidents', [OperationalIncidentController::class, 'index'])->name('incidents.index');
        Route::get('incidents/create', [OperationalIncidentController::class, 'create'])->name('incidents.create');
        Route::post('incidents', [OperationalIncidentController::class, 'store'])->name('incidents.store');
        Route::get('incidents/sla-policies', [IncidentSlaPolicyController::class, 'index'])->name('incidents.sla-policies.index');
        Route::get('incidents/sla-policies/create', [IncidentSlaPolicyController::class, 'create'])->name('incidents.sla-policies.create');
        Route::post('incidents/sla-policies', [IncidentSlaPolicyController::class, 'store'])->name('incidents.sla-policies.store');
        Route::post('incidents/detect', [IncidentDetectionController::class, 'store'])->name('incidents.detect');
        Route::get('incidents/reports', [IncidentReportController::class, 'index'])->name('incidents.reports.index');
        Route::post('incidents/reports/export', [IncidentExportController::class, 'store'])->name('incidents.reports.export');
        Route::get('incidents/{incident}', [OperationalIncidentController::class, 'show'])->name('incidents.show');
        Route::get('incidents/{incident}/edit', [OperationalIncidentController::class, 'edit'])->name('incidents.edit');
        Route::match(['put', 'patch'], 'incidents/{incident}', [OperationalIncidentController::class, 'update'])->name('incidents.update');
        Route::post('incidents/{incident}/assign', [IncidentAssignmentController::class, 'store'])->name('incidents.assign');
        Route::post('incidents/{incident}/status', [IncidentStatusController::class, 'store'])->name('incidents.status');
        Route::post('incidents/{incident}/comments', [IncidentCommentController::class, 'store'])->name('incidents.comments.store');
        Route::post('incidents/{incident}/root-cause', [IncidentRootCauseController::class, 'store'])->name('incidents.root-cause.store');
        Route::post('incidents/{incident}/corrective-actions', [IncidentCorrectiveActionController::class, 'store'])->name('incidents.corrective-actions.store');
        Route::patch('incidents/{incident}/corrective-actions/{action}', [IncidentCorrectiveActionController::class, 'update'])->name('incidents.corrective-actions.update');
        Route::post('incidents/{incident}/corrective-actions/{action}/done', [IncidentCorrectiveActionController::class, 'done'])->name('incidents.corrective-actions.done');
        Route::post('incidents/{incident}/corrective-actions/{action}/verify', [IncidentCorrectiveActionController::class, 'verify'])->name('incidents.corrective-actions.verify');

        Route::get('imports', [ImportController::class, 'index'])->name('imports.index');
        Route::get('imports/create', [ImportController::class, 'create'])->name('imports.create');
        Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
        Route::get('imports/{batch}', [ImportController::class, 'show'])->name('imports.show');
        Route::post('imports/{batch}/rollback', ImportRollbackController::class)->name('imports.rollback');

        Route::get('proposals', [OrderProposalController::class, 'index'])->name('proposals.index');
        Route::get('proposals/{proposal}', [OrderProposalController::class, 'show'])->name('proposals.show');
        Route::get('proposals/{proposal}/items/{item}', [OrderProposalController::class, 'showItem'])->name('proposals.items.show');
        Route::post('proposals/{proposal}/items/{item}/approve', [OrderProposalItemDecisionController::class, 'approve'])->name('proposals.items.approve');
        Route::post('proposals/{proposal}/items/{item}/adjust', [OrderProposalItemDecisionController::class, 'adjust'])->name('proposals.items.adjust');
        Route::post('proposals/{proposal}/items/{item}/reject', [OrderProposalItemDecisionController::class, 'reject'])->name('proposals.items.reject');
        Route::post('proposals/{proposal}/approve', [OrderProposalApprovalController::class, 'approve'])->name('proposals.approve');
        Route::post('proposals/{proposal}/convert-to-supplier-order', ConvertProposalToSupplierOrderController::class)->name('proposals.convert-to-supplier-order');

        Route::get('supplier-orders', [SupplierOrderController::class, 'index'])->name('supplier-orders.index');
        Route::get('supplier-orders/{order}', [SupplierOrderController::class, 'show'])->name('supplier-orders.show');
        Route::get('supplier-orders/{order}/confirmations/create', [ManualSupplierConfirmationController::class, 'create'])->name('supplier-orders.confirmations.create');
        Route::post('supplier-orders/{order}/confirmations', [ManualSupplierConfirmationController::class, 'store'])->name('supplier-orders.confirmations.store');
        Route::post('supplier-orders/{order}/export', [SupplierOrderExportController::class, 'store'])->name('supplier-orders.export');
        Route::post('supplier-orders/{order}/prepare-email', [SupplierOrderEmailDraftController::class, 'store'])->name('supplier-orders.prepare-email');
        Route::post('supplier-orders/{order}/approve-email', [SupplierOrderEmailApprovalController::class, 'store'])->name('supplier-orders.approve-email');
        Route::post('supplier-orders/{order}/send-email', [SupplierOrderSendController::class, 'store'])->name('supplier-orders.send-email');
        Route::post('supplier-orders/{order}/manufacturer-form-export', [ManufacturerFormExportController::class, 'store'])->name('supplier-orders.manufacturer-form-export');
        Route::get('exports/{exportFile}/download', ExportDownloadController::class)->name('exports.download');

        Route::get('emails', [EmailMessageController::class, 'index'])->name('emails.index');
        Route::get('emails/create-manual', [ManualInboundEmailController::class, 'create'])->name('emails.create-manual');
        Route::post('emails/manual', [ManualInboundEmailController::class, 'store'])->name('emails.manual.store');
        Route::get('emails/{email}', [EmailMessageController::class, 'show'])->name('emails.show');
        Route::post('emails/{email}/analyze', [AnalyzeInboundEmailController::class, 'store'])->name('emails.analyze');
        Route::get('emails/{email}/autofill', [EmailFormAutofillController::class, 'create'])->name('emails.autofill.create');
        Route::post('emails/{email}/autofill/preview', [EmailFormAutofillController::class, 'preview'])->name('emails.autofill.preview');
        Route::get('ai-extractions', [AiEmailExtractionController::class, 'index'])->name('ai-extractions.index');
        Route::get('ai-extractions/{extraction}', [AiEmailExtractionController::class, 'show'])->name('ai-extractions.show');
        Route::post('ai-extractions/{extraction}/review', [AiEmailExtractionReviewController::class, 'store'])->name('ai-extractions.review');
        Route::post('ai-extractions/{extraction}/apply-supplier-confirmation', [ApplyAiSupplierConfirmationController::class, 'store'])->name('ai-extractions.apply-supplier-confirmation');
        Route::post('ai-extractions/{extraction}/apply-carrier-quote', [ApplyAiCarrierQuoteController::class, 'store'])->name('ai-extractions.apply-carrier-quote');
        Route::post('ai-extractions/{extraction}/accept', [AiEmailExtractionReviewController::class, 'store'])->defaults('decision', 'accept')->name('ai-extractions.accept');
        Route::post('ai-extractions/{extraction}/reject', [AiEmailExtractionReviewController::class, 'store'])->defaults('decision', 'reject')->name('ai-extractions.reject');
        Route::post('ai-extractions/{extraction}/request-human-review', [AiEmailExtractionReviewController::class, 'store'])->defaults('decision', 'needs_review')->name('ai-extractions.request-human-review');

        Route::get('forms/templates', [FormTemplateController::class, 'index'])->name('forms.templates.index');
        Route::get('forms/templates/create', [FormTemplateController::class, 'create'])->name('forms.templates.create');
        Route::post('forms/templates', [FormTemplateController::class, 'store'])->name('forms.templates.store');
        Route::get('forms/templates/{template}', [FormTemplateController::class, 'show'])->name('forms.templates.show');
        Route::get('forms/templates/{template}/edit', [FormTemplateController::class, 'edit'])->name('forms.templates.edit');
        Route::match(['put', 'patch'], 'forms/templates/{template}', [FormTemplateController::class, 'update'])->name('forms.templates.update');
        Route::post('forms/templates/{template}/fields', [FormTemplateFieldController::class, 'store'])->name('forms.templates.fields.store');
        Route::post('forms/templates/{template}/manufacturer-file', [ManufacturerFormTemplateController::class, 'upload'])->name('forms.templates.manufacturer-file.upload');
        Route::post('forms/templates/{template}/mapping', [ManufacturerFormMappingController::class, 'store'])->name('forms.templates.mapping.store');
        Route::post('forms/templates/{template}/preview', [ManufacturerFormPreviewController::class, 'store'])->name('forms.templates.preview');

        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::get('suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::match(['put', 'patch'], 'suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        Route::get('inbound-orders', [InboundOrderController::class, 'index'])->name('inbound-orders.index');
        Route::get('inbound-orders/create', [InboundOrderController::class, 'create'])->name('inbound-orders.create');
        Route::post('inbound-orders', [InboundOrderController::class, 'store'])->name('inbound-orders.store');
        Route::get('inbound-orders/{inboundOrder}', [InboundOrderController::class, 'show'])->name('inbound-orders.show');
        Route::get('inbound-orders/{inboundOrder}/edit', [InboundOrderController::class, 'edit'])->name('inbound-orders.edit');
        Route::match(['put', 'patch'], 'inbound-orders/{inboundOrder}', [InboundOrderController::class, 'update'])->name('inbound-orders.update');
        Route::delete('inbound-orders/{inboundOrder}', [InboundOrderController::class, 'destroy'])->name('inbound-orders.destroy');

        Route::get('reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::get('reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
        Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
        Route::get('reservations/{reservation}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');
        Route::match(['put', 'patch'], 'reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
        Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');

        Route::get('calculations', [CalculationRunController::class, 'index'])->name('calculations.index');
        Route::get('calculations/create', [CalculationRunController::class, 'create'])->name('calculations.create');
        Route::post('calculations', [CalculationRunController::class, 'store'])->name('calculations.store');
        Route::get('calculations/{calculationRun}', [CalculationRunController::class, 'show'])->name('calculations.show');
        Route::get('calculations/{calculationRun}/edit', [CalculationRunController::class, 'edit'])->name('calculations.edit');
        Route::match(['put', 'patch'], 'calculations/{calculationRun}', [CalculationRunController::class, 'update'])->name('calculations.update');
        Route::delete('calculations/{calculationRun}', [CalculationRunController::class, 'destroy'])->name('calculations.destroy');

        Route::get('sales-history', [SalesHistoryController::class, 'index'])->name('sales-history.index');
        Route::get('sales-history/create', [SalesHistoryController::class, 'create'])->name('sales-history.create');
        Route::post('sales-history', [SalesHistoryController::class, 'store'])->name('sales-history.store');
        Route::get('sales-history/{salesHistory}', [SalesHistoryController::class, 'show'])->name('sales-history.show');
        Route::get('sales-history/{salesHistory}/edit', [SalesHistoryController::class, 'edit'])->name('sales-history.edit');
        Route::match(['put', 'patch'], 'sales-history/{salesHistory}', [SalesHistoryController::class, 'update'])->name('sales-history.update');
        Route::delete('sales-history/{salesHistory}', [SalesHistoryController::class, 'destroy'])->name('sales-history.destroy');

        Route::get('stock', [StockSnapshotController::class, 'index'])->name('stock.index');
        Route::get('stock/create', [StockSnapshotController::class, 'create'])->name('stock.create');
        Route::post('stock', [StockSnapshotController::class, 'store'])->name('stock.store');
        Route::get('stock/{stockSnapshot}', [StockSnapshotController::class, 'show'])->name('stock.show');
        Route::get('stock/{stockSnapshot}/edit', [StockSnapshotController::class, 'edit'])->name('stock.edit');
        Route::match(['put', 'patch'], 'stock/{stockSnapshot}', [StockSnapshotController::class, 'update'])->name('stock.update');
        Route::delete('stock/{stockSnapshot}', [StockSnapshotController::class, 'destroy'])->name('stock.destroy');

        Route::get('form-autofill-runs/{run}', [FormAutofillRunController::class, 'show'])->name('form-autofill-runs.show');
        Route::get('supplier-confirmations/{confirmation}', [SupplierConfirmationController::class, 'show'])->name('supplier-confirmations.show');
        Route::post('form-autofill-runs/{run}/fields/{field}/accept', [FormAutofillFieldReviewController::class, 'accept'])->name('form-autofill-runs.fields.accept');
        Route::post('form-autofill-runs/{run}/fields/{field}/update', [FormAutofillFieldReviewController::class, 'update'])->name('form-autofill-runs.fields.update');
        Route::post('form-autofill-runs/{run}/fields/{field}/reject', [FormAutofillFieldReviewController::class, 'reject'])->name('form-autofill-runs.fields.reject');
        Route::post('form-autofill-runs/{run}/validate', [FormAutofillRunValidationController::class, 'store'])->name('form-autofill-runs.validate');
        Route::post('form-autofill-runs/{run}/application-check', FormAutofillApplyGateController::class)->name('form-autofill-runs.application-check');
        Route::post('form-autofill-runs/{run}/apply-supplier-confirmation', [ApplyFormAutofillSupplierConfirmationController::class, 'store'])->name('form-autofill-runs.apply-supplier-confirmation');
        Route::post('form-autofill-runs/{run}/apply-carrier-quote', [ApplyFormAutofillCarrierQuoteController::class, 'store'])->name('form-autofill-runs.apply-carrier-quote');
        Route::post('form-autofill-runs/{run}/export', FormAutofillExportController::class)->name('form-autofill-runs.export');
        Route::get('form-autofill-outputs/{output}/download', FormAutofillOutputDownloadController::class)->name('form-autofill-outputs.download');

        Route::get('carriers', [CarrierController::class, 'index'])->name('carriers.index');
        Route::get('carriers/create', [CarrierController::class, 'create'])->name('carriers.create');
        Route::post('carriers', [CarrierController::class, 'store'])->name('carriers.store');
        Route::get('carriers/{carrier}', [CarrierController::class, 'show'])->name('carriers.show');
        Route::get('carriers/{carrier}/edit', [CarrierController::class, 'edit'])->name('carriers.edit');
        Route::match(['put', 'patch'], 'carriers/{carrier}', [CarrierController::class, 'update'])->name('carriers.update');

        Route::get('transport/quotes', [CarrierQuoteController::class, 'index'])->name('transport.quotes.index');
        Route::get('transport/quotes/{quote}', [CarrierQuoteController::class, 'show'])->name('transport.quotes.show');
        Route::get('transport/orders/{order}/quotes', [CarrierQuoteController::class, 'forSupplierOrder'])->name('transport.orders.quotes');
        Route::get('transport/orders/{order}/quotes/create', [ManualCarrierQuoteController::class, 'create'])->name('transport.orders.quotes.create');
        Route::post('transport/orders/{order}/quotes', [ManualCarrierQuoteController::class, 'store'])->name('transport.orders.quotes.store');
        Route::post('transport/orders/{order}/quotes/score', [CarrierQuoteScoringController::class, 'store'])->name('transport.orders.quotes.score');
        Route::get('transport/orders/{order}/quote-requests/create', [CarrierQuoteRequestController::class, 'create'])->name('transport.orders.quote-requests.create');
        Route::post('transport/orders/{order}/quote-requests', [CarrierQuoteRequestController::class, 'store'])->name('transport.orders.quote-requests.store');
        Route::post('transport/orders/{order}/request-quotes', [CarrierQuoteRequestController::class, 'store'])->name('transport.orders.request-quotes');
        Route::post('transport/quotes/manual', [ManualCarrierQuoteController::class, 'store'])->name('transport.quotes.manual');
        Route::post('transport/quotes/{quote}/select', [CarrierSelectionController::class, 'store'])->name('transport.quotes.select');
        Route::post('transport/quotes/{quote}/reject', [CarrierQuoteRejectionController::class, 'store'])->name('transport.quotes.reject');

        Route::get('logistics', [LogisticsController::class, 'index'])->name('logistics.index');
        Route::get('logistics/{record}', [LogisticsController::class, 'show'])->name('logistics.show');
        Route::get('logistics/{record}/edit', [LogisticsController::class, 'edit'])->name('logistics.edit');
        Route::match(['put', 'patch'], 'logistics/{record}', [LogisticsController::class, 'update'])->name('logistics.update');
        Route::post('logistics/{record}/status', [LogisticsStatusController::class, 'store'])->name('logistics.status.update');
        Route::post('logistics/{record}/update-status', [LogisticsStatusController::class, 'store'])->name('logistics.update-status');
        Route::get('logistics/{record}/receive', [GoodsReceiptController::class, 'create'])->name('logistics.receive.create');
        Route::post('logistics/{record}/receive', [GoodsReceiptController::class, 'store'])->name('logistics.receive.store');
        Route::post('logistics/export', [LogisticsExportController::class, 'store'])->name('logistics.export');
        Route::post('logistics/sync/google-sheets', [LogisticsGoogleSheetsSyncController::class, 'store'])->name('logistics.sync.google-sheets');

        Route::get('pilots', [PilotSupplierController::class, 'index'])->name('pilots.index');
        Route::get('pilots/create', [PilotSupplierController::class, 'create'])->name('pilots.create');
        Route::post('pilots', [PilotSupplierController::class, 'store'])->name('pilots.store');
        Route::get('pilots/{pilot}', [PilotSupplierController::class, 'show'])->name('pilots.show');
        Route::get('pilots/{pilot}/edit', [PilotSupplierController::class, 'edit'])->name('pilots.edit');
        Route::match(['put', 'patch'], 'pilots/{pilot}', [PilotSupplierController::class, 'update'])->name('pilots.update');
        Route::post('pilots/{pilot}/files', [PilotFileController::class, 'upload'])->name('pilots.files.upload');
        Route::delete('pilots/{pilot}/files/{file}', [PilotFileController::class, 'destroy'])->name('pilots.files.delete');
        Route::post('pilots/{pilot}/mappings/import', [PilotMappingController::class, 'saveImport'])->name('pilots.mappings.import');
        Route::post('pilots/{pilot}/mappings/manufacturer-form', [PilotMappingController::class, 'saveManufacturerForm'])->name('pilots.mappings.manufacturer-form');
        Route::post('pilots/{pilot}/mappings/email', [PilotMappingController::class, 'saveEmail'])->name('pilots.mappings.email');
        Route::post('pilots/{pilot}/mappings/carrier', [PilotMappingController::class, 'saveCarrier'])->name('pilots.mappings.carrier');
        Route::post('pilots/{pilot}/mappings/logistics', [PilotMappingController::class, 'saveLogistics'])->name('pilots.mappings.logistics');
        Route::post('pilots/{pilot}/readiness-check', [PilotReadinessController::class, 'store'])->name('pilots.readiness-check');
        Route::post('pilots/{pilot}/dry-run/{runType}', [PilotDryRunController::class, 'store'])->name('pilots.dry-run');
        Route::get('pilots/{pilot}/uat', [PilotUatChecklistController::class, 'show'])->name('pilots.uat');
        Route::post('pilots/{pilot}/uat', [PilotUatChecklistController::class, 'update'])->name('pilots.uat.update');
        Route::post('pilots/{pilot}/approve-uat', [PilotApprovalController::class, 'approveUat'])->name('pilots.approve-uat');
        Route::post('pilots/{pilot}/approve-live', [PilotApprovalController::class, 'approveLive'])->name('pilots.approve-live');
        Route::post('pilots/{pilot}/block', [PilotApprovalController::class, 'block'])->name('pilots.block');
        Route::post('pilots/{pilot}/reports/export', [PilotReportController::class, 'export'])->name('pilots.reports.export');

        Route::get('notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
        Route::post('notifications/{notification}/read', [NotificationReadController::class, 'markAsRead'])->name('notifications.read');
        Route::post('notifications/read-all', [NotificationReadController::class, 'markAllAsRead'])->name('notifications.read-all');

        Route::get('health', [HealthCheckController::class, 'index'])->name('health.index');
    });
