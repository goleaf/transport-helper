<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\EmailMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class EmailMessageController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', EmailMessage::class);

        $emails = EmailMessage::query()
            ->select([
                'id',
                'company_id',
                'email_account_id',
                'direction',
                'message_id',
                'thread_id',
                'from_email',
                'subject',
                'received_at',
                'sent_at',
                'related_supplier_id',
                'related_supplier_order_id',
                'status',
                'created_at',
            ])
            ->with([
                'emailAccount:id,name,email_address',
                'relatedSupplier:id,name',
                'relatedSupplierOrder:id,order_number',
            ])
            ->withCount(['attachments', 'aiEmailExtractions'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('supply.emails.index', [
            'emails' => $emails,
        ]);
    }

    public function show(EmailMessage $email): View
    {
        Gate::authorize('view', $email);

        $email->load([
            'emailAccount:id,name,email_address',
            'relatedSupplier:id,name',
            'relatedSupplierOrder:id,order_number',
            'attachments:id,email_message_id,original_filename,stored_path,mime_type,size_bytes,checksum',
            'aiEmailExtractions:id,email_message_id,prompt_version,confidence,requires_human_review,review_reason,accepted_at,rejected_at,created_at',
        ]);

        return view('supply.emails.show', [
            'email' => $email,
        ]);
    }
}
