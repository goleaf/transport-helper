<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationReadController extends Controller
{
    public function markAsRead(Request $request, string $notification, AuditLogService $auditLogService): RedirectResponse
    {
        $model = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $model->markAsRead();
        $auditLogService->write('notification_marked_read', null, $request->user(), null, null, [
            'notification_id' => $notification,
        ]);

        return back()->with('status', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        $auditLogService->write('notification_marked_read', null, $request->user(), null, null, [
            'scope' => 'all',
        ]);

        return redirect()
            ->route('supply.notifications.index')
            ->with('status', 'Notifications marked as read.');
    }
}
