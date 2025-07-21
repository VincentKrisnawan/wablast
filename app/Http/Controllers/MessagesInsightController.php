<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\MessageSession;
use App\Models\UploadContact;
use App\Models\MessageTemplate; // Pastikan ini di-import
use Illuminate\Support\Facades\Auth; // Pastikan ini di-import
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // Pastikan ini di-import
use Illuminate\Support\Str;

class MessagesInsightController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('WAHA Webhook Received:', $payload);

        if (isset($payload['event']) && $payload['event'] === 'message.ack') {
            $this->handleMessageAck($payload);
        } elseif (isset($payload['event']) && $payload['event'] === 'message') {
            $this->handleIncomingMessage($payload);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleMessageAck($payload)
    {
        Log::info('Handling message.ack event.', $payload);

        $ackPayload = $payload['payload'] ?? [];
        $wahaMessageId = $ackPayload['id']['_serialized'] ?? (is_string($ackPayload['id']) ? $ackPayload['id'] : null);
        $ackName = $ackPayload['ackName'] ?? null;

        if (!$wahaMessageId || !$ackName) {
            Log::warning('Missing WAHA Message ID or ACK Name in payload.', $payload);
            return;
        }

        $message = Message::where('waha_message_id', $wahaMessageId)->first();

        if ($message) {
            if (in_array($ackName, ['READ', 'PLAYED']) && $message->status !== 'read' && $message->status !== 'replied') {
                $message->status = 'read';
                $message->read_at = now();
                $message->save();
                Log::info('Message status updated to read.', ['message_id' => $message->id]);
            }
        } else {
            Log::warning('Message not found for WAHA ID.', ['wahaMessageId' => $wahaMessageId]);
        }
    }

    protected function handleIncomingMessage($payload)
    {
        Log::info('Handling incoming message event.', $payload);

        $from = $payload['payload']['from'] ?? null;
        $to = $payload['payload']['to'] ?? null;
        $isFromMe = $payload['payload']['fromMe'] ?? false;

        if ($isFromMe || !$from || !$to) {
            return;
        }

        $cleanFrom = Str::before($from, '@');
        $cleanTo = Str::before($to, '@');

        // Find the last message sent to this contact from the system
        $outgoingMessage = Message::where('to_number', $cleanFrom)
                                   ->where('from_number', 'like', '%' . $cleanTo . '%')
                                   ->whereIn('status', ['sent', 'read'])
                                   ->latest('sent_at')
                                   ->first();

        if ($outgoingMessage) {
            $outgoingMessage->status = 'replied';
            $outgoingMessage->replied_at = now();
            $outgoingMessage->save();
            Log::info('Outgoing message status updated to replied.', ['message_id' => $outgoingMessage->id]);
        } else {
            Log::info('No matching outgoing message found for incoming message.', [
                'webhook_from' => $from,
                'webhook_to' => $to,
                'body' => $payload['payload']['body'] ?? ''
            ]);
        }
    }

    /**
     * PERBAIKAN: Method ini sekarang berisi logika yang benar untuk menampilkan dashboard.
     */
    public function dashboard(Request $request)
    {
        $userId = Auth::id();

        $sessionIds = MessageSession::whereHas('batch', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->pluck('id');

        $totalMessages = Message::whereIn('session_id', $sessionIds)->count();
        $readMessages = Message::whereIn('session_id', $sessionIds)->where('status', 'read')->count();
        $repliedMessages = Message::whereIn('session_id', $sessionIds)->where('status', 'replied')->count();
        $sentMessages = $totalMessages - ($readMessages + $repliedMessages);

        $sessions = MessageSession::whereIn('id', $sessionIds)
                                  ->withCount([
                                      'messages',
                                      'messages as read_messages_count' => fn ($q) => $q->where('status', 'read'),
                                      'messages as replied_messages_count' => fn ($q) => $q->where('status', 'replied'),
                                  ])
                                  ->orderBy('session_number', 'asc')
                                  ->paginate(5);

        $activeSessionId = $request->query('session', $sessions->first()->id ?? null);

        $messagesBySession = [];
        foreach ($sessions as $session) {
            $messagesBySession[$session->id] = Message::where('session_id', $session->id)
                                                      ->with('contact')
                                                      ->latest('sent_at')
                                                      ->paginate(10, ['*'], 'page');
        }

        return view('pages.dashboard', [
            'totalMessages' => $totalMessages,
            'sentMessages' => $sentMessages,
            'readMessages' => $readMessages,
            'repliedMessages' => $repliedMessages,
            'sessions' => $sessions,
            'messages' => $messagesBySession,
            'activeSession' => $activeSessionId,
        ]);
    }

    public function sessionDetails($sessionId)
    {
        $session = MessageSession::with(['messages.contact'])->findOrFail($sessionId);
        return view('pages.session_details', compact('session'));
    }
}
