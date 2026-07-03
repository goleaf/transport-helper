<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SupplierConfirmationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SupplierConfirmation::class);

        $confirmations = SupplierConfirmation::query()
            ->select([
                'id',
                'company_id',
                'supplier_order_id',
                'supplier_reference',
                'confirmation_date',
                'ready_date',
                'expected_arrival_date',
                'status',
                'source_type',
                'source_id',
                'discrepancy_summary',
                'applied_by_user_id',
                'applied_at',
                'created_at',
            ])
            ->with(['supplierOrder:id,supplier_id,order_number,status', 'supplierOrder.supplier:id,name', 'appliedBy:id,name'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('supplier_order_id'), fn ($query) => $query->where('supplier_order_id', $request->integer('supplier_order_id')))
            ->when($request->filled('source_type'), fn ($query) => $query->where('source_type', $request->string('source_type')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('confirmation_date', '>=', $request->string('date_from')->toString()))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('confirmation_date', '<=', $request->string('date_to')->toString()))
            ->when($request->boolean('needs_review'), fn ($query) => $query->where('status', 'needs_review'))
            ->when($request->filled('supplier_id'), function ($query) use ($request): void {
                $query->whereHas('supplierOrder', fn ($orderQuery) => $orderQuery->where('supplier_id', $request->integer('supplier_id')));
            })
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.supplier-confirmations.index', [
            'confirmations' => $confirmations,
            'suppliers' => Supplier::query()->select(['id', 'name'])->orderBy('name')->limit(250)->get(),
            'filters' => $request->only(['status', 'supplier_id', 'supplier_order_id', 'source_type', 'date_from', 'date_to', 'needs_review']),
        ]);
    }

    public function show(SupplierConfirmation $confirmation): View
    {
        Gate::authorize('view', $confirmation);

        $confirmation->load([
            'supplierOrder.supplier:id,name',
            'supplierOrder.items.product:id,sku,name,manufacturer_sku',
            'emailMessage:id,subject,from_email',
            'aiEmailExtraction:id,email_message_id,accepted_at,rejected_at',
            'formAutofillRun:id,status,form_template_id',
            'formAutofillRun.formTemplate:id,name,context_type',
            'appliedBy:id,name',
            'items.product:id,sku,name,manufacturer_sku',
        ]);

        $auditLogs = AuditLog::query()
            ->select(['id', 'event_type', 'user_id', 'created_at'])
            ->where('auditable_type', $confirmation::class)
            ->where('auditable_id', $confirmation->getKey())
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('supply.supplier-confirmations.show', [
            'confirmation' => $confirmation,
            'auditLogs' => $auditLogs,
        ]);
    }
}
