<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EmailMessage;
use App\Models\ExportFile;
use App\Models\Supplier;
use App\Models\SupplierConfirmation;
use App\Models\SupplierOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SupplierOrderController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', SupplierOrder::class);

        $orders = SupplierOrder::query()
            ->select([
                'id',
                'company_id',
                'supplier_id',
                'order_proposal_id',
                'order_number',
                'status',
                'order_date',
                'sent_at',
                'created_at',
                'updated_at',
            ])
            ->with('supplier:id,name')
            ->withCount(['items', 'emailMessages'])
            ->withSum('items', 'ordered_quantity')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('order_date_from'), fn ($query) => $query->whereDate('order_date', '>=', $request->string('order_date_from')->toString()))
            ->when($request->filled('order_date_to'), fn ($query) => $query->whereDate('order_date', '<=', $request->string('order_date_to')->toString()))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.supplier-orders.index', [
            'orders' => $orders,
            'suppliers' => Supplier::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->limit(250)
                ->get(),
            'filters' => $request->only(['status', 'supplier_id', 'order_date_from', 'order_date_to']),
        ]);
    }

    public function show(SupplierOrder $order): View
    {
        Gate::authorize('view', $order);

        $order->load([
            'company:id,name',
            'supplier:id,name,code,default_language,default_currency',
            'supplier.contacts:id,supplier_id,name,email,receives_orders,is_active',
            'orderProposal:id,status',
            'items.product:id,sku,manufacturer_sku,name,unit',
            'confirmations:id,supplier_order_id,supplier_reference,status,confirmation_date,ready_date,expected_arrival_date,discrepancy_summary,applied_at',
            'logisticsRecords:id,supplier_order_id,carrier_id,selected_carrier_quote_id,status,order_date,ready_date,pickup_date,delivery_date,transport_price,currency',
            'logisticsRecords.carrier:id,name',
            'logisticsRecords.selectedCarrierQuote:id,carrier_id,price,currency,delivery_date,status',
            'carrierQuotes:id,supplier_order_id,carrier_id,price,currency,pickup_date,delivery_date,calculated_score,status',
            'carrierQuotes.carrier:id,name',
            'emailMessages:id,company_id,related_supplier_order_id,direction,to_json,cc_json,subject,body_text,status,message_id,sent_at,created_at',
            'emailMessages.attachments:id,email_message_id,original_filename,stored_path,mime_type,size_bytes',
            'sentBy:id,name',
            'emailApprovedBy:id,name',
        ]);

        $exportFiles = ExportFile::query()
            ->select(['id', 'company_id', 'export_type', 'related_model_type', 'related_model_id', 'filename', 'stored_path', 'mime_type', 'status', 'created_by_user_id', 'created_at'])
            ->where('related_model_type', $order::class)
            ->where('related_model_id', $order->id)
            ->with('createdBy:id,name')
            ->orderByDesc('id')
            ->get();

        $auditLogs = AuditLog::query()
            ->select(['id', 'user_id', 'event_type', 'metadata_json', 'created_at'])
            ->where('auditable_type', $order::class)
            ->where('auditable_id', $order->id)
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('supply.supplier-orders.show', [
            'order' => $order,
            'exportFiles' => $exportFiles,
            'emailMessage' => $this->currentEmailMessage($order),
            'auditLogs' => $auditLogs,
            'itemsCount' => $order->items->count(),
            'totalOrderedQuantity' => $order->items->sum(fn ($item): float => (float) $item->ordered_quantity),
            'firstLogisticsRecord' => $order->logisticsRecords->first(),
            'canExport' => Gate::allows('export', $order),
            'canPrepareEmail' => Gate::allows('prepareEmail', $order),
            'canApproveEmail' => Gate::allows('approveEmail', $order),
            'canSendEmail' => Gate::allows('sendEmail', $order),
            'canCreateManualConfirmation' => Gate::allows('createManual', SupplierConfirmation::class),
            'canManageTransport' => request()->user()?->canManageLogisticsWorkflow() ?? false,
        ]);
    }

    private function currentEmailMessage(SupplierOrder $order): ?EmailMessage
    {
        if (is_string($order->email_message_id) && ctype_digit($order->email_message_id)) {
            $email = EmailMessage::query()
                ->with('attachments:id,email_message_id,original_filename,stored_path,mime_type,size_bytes')
                ->whereKey((int) $order->email_message_id)
                ->first();

            if ($email instanceof EmailMessage) {
                return $email;
            }
        }

        return $order->emailMessages
            ->filter(fn (EmailMessage $message): bool => ($message->direction instanceof \BackedEnum ? $message->direction->value : $message->direction) === 'outbound')
            ->sortByDesc('id')
            ->first();
    }
}
