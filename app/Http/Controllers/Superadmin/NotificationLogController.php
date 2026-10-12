<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = Notification::with(['user', 'company'])
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->paginate(50)->withQueryString();

        $types = Notification::distinct()->pluck('type')->sort();

        $stats = [
            'total' => Notification::count(),
            'unread' => Notification::unread()->count(),
            'today' => Notification::whereDate('created_at', today())->count(),
            'push_errors' => Notification::whereNotNull('push_error')->count(),
        ];

        return view('superadmin.system.notifications.index', compact('notifications', 'types', 'stats'));
    }
}
