<?php

use App\Http\Controllers\Supply\AiEmailExtractionController;
use App\Http\Controllers\Supply\AiEmailExtractionReviewController;
use App\Http\Controllers\Supply\AnalyzeInboundEmailController;
use App\Http\Controllers\Supply\CarrierQuoteDecisionController;
use App\Http\Controllers\Supply\CarrierQuoteRequestController;
use App\Http\Controllers\Supply\ConvertProposalToSupplierOrderController;
use App\Http\Controllers\Supply\EmailFormAutofillController;
use App\Http\Controllers\Supply\EmailMessageController;
use App\Http\Controllers\Supply\ExportDownloadController;
use App\Http\Controllers\Supply\FormAutofillApplyGateController;
use App\Http\Controllers\Supply\FormAutofillExportController;
use App\Http\Controllers\Supply\FormAutofillFieldReviewController;
use App\Http\Controllers\Supply\FormAutofillOutputDownloadController;
use App\Http\Controllers\Supply\FormAutofillRunController;
use App\Http\Controllers\Supply\FormAutofillRunValidationController;
use App\Http\Controllers\Supply\FormTemplateController;
use App\Http\Controllers\Supply\FormTemplateFieldController;
use App\Http\Controllers\Supply\ImportController;
use App\Http\Controllers\Supply\ImportRollbackController;
use App\Http\Controllers\Supply\LogisticsController;
use App\Http\Controllers\Supply\LogisticsExportController;
use App\Http\Controllers\Supply\LogisticsGoogleSheetsSyncController;
use App\Http\Controllers\Supply\ManualCarrierQuoteController;
use App\Http\Controllers\Supply\ManualInboundEmailController;
use App\Http\Controllers\Supply\OrderProposalApprovalController;
use App\Http\Controllers\Supply\OrderProposalController;
use App\Http\Controllers\Supply\OrderProposalItemDecisionController;
use App\Http\Controllers\Supply\SupplierOrderController;
use App\Http\Controllers\Supply\SupplierOrderEmailApprovalController;
use App\Http\Controllers\Supply\SupplierOrderEmailDraftController;
use App\Http\Controllers\Supply\SupplierOrderExportController;
use App\Http\Controllers\Supply\SupplierOrderSendController;
use App\Http\Controllers\Supply\SupplyDashboardController;
use App\Http\Controllers\Supply\SupplySectionController;
use App\Http\Controllers\Supply\TransportQuoteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web'])
    ->prefix('supply')
    ->name('supply.')
    ->group(function (): void {
        Route::get('/', SupplyDashboardController::class)->name('dashboard');
        Route::get('dashboard', SupplyDashboardController::class)->name('dashboard.show');
        Route::get('products', [SupplySectionController::class, 'show'])->defaults('section', 'products')->name('products.index');
        Route::get('suppliers', [SupplySectionController::class, 'show'])->defaults('section', 'suppliers')->name('suppliers.index');
        Route::get('stock', [SupplySectionController::class, 'show'])->defaults('section', 'stock')->name('stock.index');
        Route::get('sales-history', [SupplySectionController::class, 'show'])->defaults('section', 'sales-history')->name('sales-history.index');
        Route::get('inbound-orders', [SupplySectionController::class, 'show'])->defaults('section', 'inbound-orders')->name('inbound-orders.index');
        Route::get('reservations', [SupplySectionController::class, 'show'])->defaults('section', 'reservations')->name('reservations.index');
        Route::get('calculations', [SupplySectionController::class, 'show'])->defaults('section', 'calculations')->name('calculations.index');
        Route::get('form-autofill-runs', [FormAutofillRunController::class, 'index'])->name('form-autofill-runs.index');
        Route::get('supplier-confirmations', [SupplySectionController::class, 'show'])->defaults('section', 'supplier-confirmations')->name('supplier-confirmations.index');
        Route::get('exports', [SupplySectionController::class, 'show'])->defaults('section', 'exports')->name('exports.index');
        Route::get('audit-logs', [SupplySectionController::class, 'show'])->defaults('section', 'audit-logs')->name('audit-logs.index');
        Route::get('settings', [SupplySectionController::class, 'show'])->defaults('section', 'settings')->name('settings.index');
        Route::get('integrations', [SupplySectionController::class, 'show'])->defaults('section', 'integrations')->name('integrations.index');

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
        Route::post('supplier-orders/{order}/export', [SupplierOrderExportController::class, 'store'])->name('supplier-orders.export');
        Route::post('supplier-orders/{order}/prepare-email', [SupplierOrderEmailDraftController::class, 'store'])->name('supplier-orders.prepare-email');
        Route::post('supplier-orders/{order}/approve-email', [SupplierOrderEmailApprovalController::class, 'store'])->name('supplier-orders.approve-email');
        Route::post('supplier-orders/{order}/send-email', [SupplierOrderSendController::class, 'store'])->name('supplier-orders.send-email');
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

        Route::get('form-autofill-runs/{run}', [FormAutofillRunController::class, 'show'])->name('form-autofill-runs.show');
        Route::post('form-autofill-runs/{run}/fields/{field}/accept', [FormAutofillFieldReviewController::class, 'accept'])->name('form-autofill-runs.fields.accept');
        Route::post('form-autofill-runs/{run}/fields/{field}/update', [FormAutofillFieldReviewController::class, 'update'])->name('form-autofill-runs.fields.update');
        Route::post('form-autofill-runs/{run}/fields/{field}/reject', [FormAutofillFieldReviewController::class, 'reject'])->name('form-autofill-runs.fields.reject');
        Route::post('form-autofill-runs/{run}/validate', [FormAutofillRunValidationController::class, 'store'])->name('form-autofill-runs.validate');
        Route::post('form-autofill-runs/{run}/application-check', FormAutofillApplyGateController::class)->name('form-autofill-runs.application-check');
        Route::post('form-autofill-runs/{run}/export', FormAutofillExportController::class)->name('form-autofill-runs.export');
        Route::get('form-autofill-outputs/{output}/download', FormAutofillOutputDownloadController::class)->name('form-autofill-outputs.download');

        Route::get('transport/quotes', [TransportQuoteController::class, 'index'])->name('transport.quotes.index');
        Route::get('transport/orders/{supplierOrder}/quotes', [TransportQuoteController::class, 'orderQuotes'])->name('transport.orders.quotes.index');
        Route::post('transport/orders/{supplierOrder}/request-quotes', [CarrierQuoteRequestController::class, 'store'])->name('transport.orders.request-quotes');
        Route::post('transport/quotes/manual', [ManualCarrierQuoteController::class, 'store'])->name('transport.quotes.manual');
        Route::post('transport/quotes/{quote}/select', [CarrierQuoteDecisionController::class, 'select'])->name('transport.quotes.select');
        Route::post('transport/quotes/{quote}/reject', [CarrierQuoteDecisionController::class, 'reject'])->name('transport.quotes.reject');

        Route::get('logistics', [LogisticsController::class, 'index'])->name('logistics.index');
        Route::get('logistics/{record}', [LogisticsController::class, 'show'])->name('logistics.show');
        Route::post('logistics/{record}/update-status', [LogisticsController::class, 'updateStatus'])->name('logistics.update-status');
        Route::post('logistics/export', [LogisticsExportController::class, 'store'])->name('logistics.export');
        Route::post('logistics/sync/google-sheets', [LogisticsGoogleSheetsSyncController::class, 'store'])->name('logistics.sync.google-sheets');
    });
