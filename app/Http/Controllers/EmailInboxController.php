<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailInboxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EmailLog::with('order')->latest('sent_at')->limit(20);

        if (Auth::check() && Auth::user()->role === 'customer') {
            $query->where('user_id', Auth::id());
        }

        $logs = $query->get()->map(fn (EmailLog $log) => [
            'id' => $log->id,
            'event_type' => $log->event_type,
            'recipient_email' => $log->recipient_email,
            'subject' => $log->subject,
            'body_html' => $log->body_html,
            'smtp_status' => $log->smtp_status,
            'order_number' => $log->order?->order_number,
            'sent_at_human' => $log->sent_at?->format('M d, Y g:i A'),
        ]);

        return response()->json([
            'ok' => true,
            'emails' => $logs,
        ]);
    }
}
