<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\ExportFile;
use App\Models\SupplierOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class SupplierOrderController extends Controller
{
    public function index(): View
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
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.supplier-orders.index', [
            'orders' => $orders,
        ]);
    }

    public function show(SupplierOrder $order): View
    {
        Gate::authorize('view', $order);

        $order->load([
            'supplier:id,name,default_language,default_currency',
            'supplier.contacts:id,supplier_id,name,email,receives_orders,is_active',
            'items.product:id,sku,name',
            'logisticsRecords:id,supplier_order_id,status,order_date,ready_date,pickup_date,delivery_date,transport_price,currency',
            'emailMessages:id,related_supplier_order_id,direction,subject,status,message_id,sent_at,created_at',
        ]);

        $exportFiles = ExportFile::query()
            ->select(['id', 'export_type', 'filename', 'stored_path', 'status', 'created_at'])
            ->where('related_model_type', $order::class)
            ->where('related_model_id', $order->id)
            ->orderByDesc('id')
            ->get();

        return view('supply.supplier-orders.show', [
            'order' => $order,
            'exportFiles' => $exportFiles,
            'canExport' => Gate::allows('export', $order),
            'canPrepareEmail' => Gate::allows('prepareEmail', $order),
            'canApproveEmail' => Gate::allows('approveEmail', $order),
            'canSendEmail' => Gate::allows('sendEmail', $order),
        ]);
    }
}
