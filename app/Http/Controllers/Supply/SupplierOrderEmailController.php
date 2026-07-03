<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supply\SendSupplierOrderEmailRequest;
use App\Models\SupplierOrder;
use App\Services\Supply\SupplierOrderEmailDraftService;
use App\Services\Supply\SupplierOrderSendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SupplierOrderEmailController extends Controller
{
    public function prepare(
        Request $request,
        SupplierOrder $order,
        SupplierOrderEmailDraftService $draftService,
    ): RedirectResponse {
        Gate::authorize('prepareEmail', $order);

        $emailMessage = $draftService->prepareDraft($order, $request->user());

        return redirect()
            ->route('supply.supplier-orders.show', $order)
            ->with('status', "Email draft {$emailMessage->id} prepared.");
    }

    public function approve(
        Request $request,
        SupplierOrder $order,
        SupplierOrderEmailDraftService $draftService,
    ): RedirectResponse {
        Gate::authorize('approveEmail', $order);

        $emailMessage = $draftService->approveDraft($order, $request->user());

        return redirect()
            ->route('supply.supplier-orders.show', $order)
            ->with('status', "Email draft {$emailMessage->id} approved.");
    }

    public function send(
        SendSupplierOrderEmailRequest $request,
        SupplierOrder $order,
        SupplierOrderSendService $sendService,
    ): RedirectResponse {
        $emailMessage = $sendService->send($order, $request->user(), [
            'no_attachment_confirmed' => $request->boolean('no_attachment_confirmed'),
        ]);

        return redirect()
            ->route('supply.supplier-orders.show', $order)
            ->with('status', "Email {$emailMessage->message_id} sent.");
    }
}
