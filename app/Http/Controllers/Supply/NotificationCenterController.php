<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NotificationCenterController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('supply.notifications.index', [
            'notifications' => $notifications,
        ]);
    }
}
