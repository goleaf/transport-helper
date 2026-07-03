<?php

use App\Http\Controllers\Supply\AiEmailExtractionController;
use App\Http\Controllers\Supply\CarrierQuoteDecisionController;
use App\Http\Controllers\Supply\CarrierQuoteRequestController;
use App\Http\Controllers\Supply\ConvertProposalToSupplierOrderController;
use App\Http\Controllers\Supply\EmailFormAutofillController;
use App\Http\Controllers\Supply\EmailMessageController;
use App\Http\Controllers\Supply\FormAutofillApplyController;
use App\Http\Controllers\Supply\FormAutofillExportController;
use App\Http\Controllers\Supply\FormAutofillFieldReviewController;
use App\Http\Controllers\Supply\FormAutofillRunController;
use App\Http\Controllers\Supply\FormTemplateController;
use App\Http\Controllers\Supply\FormTemplateFieldController;
use App\Http\Controllers\Supply\ImportBatchController;
use App\Http\Controllers\Supply\LogisticsController;
use App\Http\Controllers\Supply\LogisticsExportController;
use App\Http\Controllers\Supply\LogisticsGoogleSheetsSyncController;
use App\Http\Controllers\Supply\ManualCarrierQuoteController;
use App\Http\Controllers\Supply\OrderProposalController;
use App\Http\Controllers\Supply\OrderProposalItemDecisionController;
use App\Http\Controllers\Supply\SupplierOrderController;
use App\Http\Controllers\Supply\SupplierOrderEmailController;
use App\Http\Controllers\Supply\SupplierOrderExportController;
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
        Route::get('ai-extractions', [SupplySectionController::class, 'show'])->defaults('section', 'ai-extractions')->name('ai-extractions.index');
        Route::get('form-autofill-runs', [SupplySectionController::class, 'show'])->defaults('section', 'form-autofill-runs')->name('form-autofill-runs.index');
        Route::get('supplier-confirmations', [SupplySectionController::class, 'show'])->defaults('section', 'supplier-confirmations')->name('supplier-confirmations.index');
        Route::get('exports', [SupplySectionController::class, 'show'])->defaults('section', 'exports')->name('exports.index');
        Route::get('audit-logs', [SupplySectionController::class, 'show'])->defaults('section', 'audit-logs')->name('audit-logs.index');
        Route::get('settings', [SupplySectionController::class, 'show'])->defaults('section', 'settings')->name('settings.index');
        Route::get('integrations', [SupplySectionController::class, 'show'])->defaults('section', 'integrations')->name('integrations.index');

        Route::get('imports', [ImportBatchController::class, 'index'])->name('imports.index');
        Route::get('imports/create', [ImportBatchController::class, 'create'])->name('imports.create');
        Route::post('imports', [ImportBatchController::class, 'store'])->name('imports.store');
        Route::get('imports/{batch}', [ImportBatchController::class, 'show'])->name('imports.show');
        Route::post('imports/{batch}/rollback', [ImportBatchController::class, 'rollback'])->name('imports.rollback');

        Route::get('proposals', [OrderProposalController::class, 'index'])->name('proposals.index');
        Route::get('proposals/{proposal}', [OrderProposalController::class, 'show'])->name('proposals.show');
        Route::get('proposals/{proposal}/items/{item}', [OrderProposalController::class, 'showItem'])->name('proposals.items.show');
        Route::post('proposals/{proposal}/items/{item}/approve', [OrderProposalItemDecisionController::class, 'approve'])->name('proposals.items.approve');
        Route::post('proposals/{proposal}/items/{item}/adjust', [OrderProposalItemDecisionController::class, 'adjust'])->name('proposals.items.adjust');
        Route::post('proposals/{proposal}/items/{item}/reject', [OrderProposalItemDecisionController::class, 'reject'])->name('proposals.items.reject');
        Route::post('proposals/{proposal}/approve', [OrderProposalController::class, 'approve'])->name('proposals.approve');
        Route::post('proposals/{proposal}/convert-to-supplier-order', ConvertProposalToSupplierOrderController::class)->name('proposals.convert-to-supplier-order');

        Route::get('supplier-orders', [SupplierOrderController::class, 'index'])->name('supplier-orders.index');
        Route::get('supplier-orders/{order}', [SupplierOrderController::class, 'show'])->name('supplier-orders.show');
        Route::post('supplier-orders/{order}/export', [SupplierOrderExportController::class, 'store'])->name('supplier-orders.export');
        Route::post('supplier-orders/{order}/prepare-email', [SupplierOrderEmailController::class, 'prepare'])->name('supplier-orders.prepare-email');
        Route::post('supplier-orders/{order}/approve-email', [SupplierOrderEmailController::class, 'approve'])->name('supplier-orders.approve-email');
        Route::post('supplier-orders/{order}/send-email', [SupplierOrderEmailController::class, 'send'])->name('supplier-orders.send-email');

        Route::get('emails', [EmailMessageController::class, 'index'])->name('emails.index');
        Route::get('emails/{email}', [EmailMessageController::class, 'show'])->name('emails.show');
        Route::get('emails/{email}/autofill', [EmailFormAutofillController::class, 'create'])->name('emails.autofill.create');
        Route::post('emails/{email}/autofill/preview', [EmailFormAutofillController::class, 'preview'])->name('emails.autofill.preview');
        Route::get('ai-extractions/{extraction}', [AiEmailExtractionController::class, 'show'])->name('ai-extractions.show');
        Route::post('ai-extractions/{extraction}/accept', [AiEmailExtractionController::class, 'accept'])->name('ai-extractions.accept');
        Route::post('ai-extractions/{extraction}/reject', [AiEmailExtractionController::class, 'reject'])->name('ai-extractions.reject');
        Route::post('ai-extractions/{extraction}/request-human-review', [AiEmailExtractionController::class, 'requestHumanReview'])->name('ai-extractions.request-human-review');

        Route::get('forms/templates', [FormTemplateController::class, 'index'])->name('forms.templates.index');
        Route::get('forms/templates/create', [FormTemplateController::class, 'create'])->name('forms.templates.create');
        Route::post('forms/templates', [FormTemplateController::class, 'store'])->name('forms.templates.store');
        Route::get('forms/templates/{template}', [FormTemplateController::class, 'show'])->name('forms.templates.show');
        Route::post('forms/templates/{template}/fields', [FormTemplateFieldController::class, 'store'])->name('forms.templates.fields.store');

        Route::get('form-autofill-runs/{run}', [FormAutofillRunController::class, 'show'])->name('form-autofill-runs.show');
        Route::post('form-autofill-runs/{run}/fields/{field}/accept', [FormAutofillFieldReviewController::class, 'accept'])->name('form-autofill-runs.fields.accept');
        Route::post('form-autofill-runs/{run}/fields/{field}/update', [FormAutofillFieldReviewController::class, 'update'])->name('form-autofill-runs.fields.update');
        Route::post('form-autofill-runs/{run}/fields/{field}/reject', [FormAutofillFieldReviewController::class, 'reject'])->name('form-autofill-runs.fields.reject');
        Route::post('form-autofill-runs/{run}/validate', [FormAutofillRunController::class, 'validateRun'])->name('form-autofill-runs.validate');
        Route::post('form-autofill-runs/{run}/apply', FormAutofillApplyController::class)->name('form-autofill-runs.apply');
        Route::post('form-autofill-runs/{run}/export', FormAutofillExportController::class)->name('form-autofill-runs.export');

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
