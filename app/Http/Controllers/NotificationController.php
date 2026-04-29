<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markRead($id)
    {
        $notification = Notification::findOrFail($id);
        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'Marked as read',
            'text' => 'Notification dismissed.',
        ]);
    }

    public function markAllRead(Request $request)
    {
        Notification::whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('swal', [
            'icon' => 'success',
            'title' => 'All caught up',
            'text' => 'All notifications marked as read.',
        ]);
    }
}
