<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner\PartnerNotification;
use App\Services\PartnerNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(private PartnerNotificationService $service) {}

    /**
     * Full notifications page.
     */
    public function index()
    {
        $partner = Auth::user();

        // Refresh auto-generated notifications
        $this->service->syncForPartner($partner->id);

        $notifications = PartnerNotification::forPartner($partner->id)
            ->orderByRaw('read_at IS NOT NULL ASC, created_at DESC')
            ->paginate(25);

        return view('partner.notifications.index', compact('notifications'));
    }

    /**
     * Get bell dropdown data (JSON for Alpine).
     */
    public function dropdown()
    {
        $partner = Auth::user();

        $this->service->syncForPartner($partner->id);

        $notifications = PartnerNotification::forPartner($partner->id)
            ->orderByRaw('read_at IS NOT NULL ASC, created_at DESC')
            ->take(10)
            ->get()
            ->map(fn($n) => [
                'id'           => $n->id,
                'title'        => $n->title,
                'body'         => $n->body,
                'action_url'   => $n->action_url,
                'action_label' => $n->action_label,
                'icon'         => $n->iconClass(),
                'colors'       => $n->colorClasses(),
                'unread'       => $n->isUnread(),
                'time'         => $n->relativeTime(),
            ]);

        $unreadCount = PartnerNotification::forPartner($partner->id)->unread()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * Mark a single notification as read and redirect to its URL.
     */
    public function markRead($id)
    {
        $notification = PartnerNotification::find($id);

        if (!$notification) {
            // It might have been an auto-notification that was removed during a sync.
            return redirect()->route('partner.notifications.index')
                ->with('info', 'This alert has been updated or resolved.');
        }

        $partner = Auth::user();

        if ($notification->partner_id !== $partner->id) {
            abort(403);
        }

        $notification->markRead();

        $url = $notification->action_url ?? route('partner.notifications.index');
        return redirect($url);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead()
    {
        $partner = Auth::user();

        PartnerNotification::forPartner($partner->id)
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
