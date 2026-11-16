<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Only librarians and authorized supervisors
        // may view notification monitoring records.
        if (!in_array($user->role, ['librarian', 'supervisor'])) {
            return response()->json([
                'message' => 'You are not authorized to view notifications.',
            ], 403);
        }

        $notifications = NotificationLog::with(['loan', 'borrower'])
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function summary(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['librarian', 'supervisor'])) {
            return response()->json([
                'message' => 'You are not authorized to view notification summaries.',
            ], 403);
        }

        return response()->json([
            'total' => NotificationLog::count(),
            'pending' => NotificationLog::where('status', 'pending')->count(),
            'retrying' => NotificationLog::where('status', 'retrying')->count(),
            'sent' => NotificationLog::where('status', 'sent')->count(),
            'failed' => NotificationLog::where('status', 'failed')->count(),
        ]);
    }
}
