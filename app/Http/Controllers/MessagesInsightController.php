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
        $wahaMessageId = $payload['payload']['id']['_serialized'] ?? null; // Perbaikan path ID
        $ackName = $payload['payload']['ackName'] ?? null;

        Log::info("WAHA Message ID: {$wahaMessageId}, ACK Name: {$ackName}");

        if ($wahaMessageId && $ackName) {
            $message = Message::where('waha_message_id', $wahaMessageId)->first();

            if ($message) {
                Log::info('Message found for update.', ['message_id' => $message->id, 'current_status' => $message->status]);
                switch ($ackName) {
                    case 'SERVER':
                    case 'DEVICE':
                        $message->status = 'sent';
                        break;
                    case 'READ':
                    case 'PLAYED': // WAHA considers played as read
                        $message->status = 'read';
                        $message->read_at = now();
                        break;
                    default:
                        Log::warning('Unknown ACK Name received.', ['ackName' => $ackName, 'wahaMessageId' => $wahaMessageId]);
                        break;
                }
                $message->save();
                Log::info('Message status updated.', ['message_id' => $message->id, 'new_status' => $message->status]);
            } else {
                Log::warning('Message not found for WAHA ID.', ['wahaMessageId' => $wahaMessageId]);
            }
        } else {
            Log::warning('Missing WAHA Message ID or ACK Name in payload.', $payload);
        }
    }

    protected function handleIncomingMessage($payload)
    {
        Log::info('Handling incoming message event.', $payload);
        $from = $payload['payload']['from'] ?? null;
        $to = $payload['payload']['to'] ?? null;

        if (!$from || !$to) {
            Log::warning('Incoming message without "from" or "to" field.', $payload);
            return;
        }

        $cleanFrom = Str::before($from, '@');
        $cleanTo = Str::before($to, '@');

        $outgoingMessage = Message::where('to_number', $cleanFrom)
                                   ->where('from_number', $cleanTo)
                                   ->whereIn('status', ['sent', 'read'])
                                   ->latest('sent_at')
                                   ->first();

        if ($outgoingMessage) {
            $outgoingMessage->status = 'replied';
            $outgoingMessage->replied_at = now();
            $outgoingMessage->save();
            Log::info('Outgoing message status updated to replied.', ['message_id' => $outgoingMessage->id]);
        } else {
            Log::info('No matching outgoing message found for incoming message.', ['webhook_from' => $from, 'webhook_to' => $to]);
        }
    }

    /**
     * PERBAIKAN: Method ini sekarang berisi logika yang benar untuk menampilkan dashboard.
     */
    public function dashboard(Request $request)
    {
        $userId = Auth::id();

        // Ambil semua ID sesi milik user
        $sessionIds = MessageSession::whereHas('batch', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->pluck('id');

        // Hitung statistik pesan secara keseluruhan
        $totalMessages = Message::whereIn('session_id', $sessionIds)->count();
        $sentMessages = Message::whereIn('session_id', $sessionIds)->where('status', 'sent')->count();
        $readMessages = Message::whereIn('session_id', $sessionIds)->where('status', 'read')->count();
        $repliedMessages = Message::whereIn('session_id', $sessionIds)->where('status', 'replied')->count();

        // Ambil sesi dengan paginasi 5 item per halaman
        $sessions = MessageSession::whereIn('id', $sessionIds)
                                  ->orderBy('session_number', 'asc')
                                  ->paginate(5);

        // Tentukan sesi mana yang harus terbuka (berdasarkan parameter URL)
        $activeSessionId = $request->query('session', $sessions->first()->id ?? null);

        // Ambil pesan untuk setiap sesi (hanya untuk sesi di halaman saat ini) dengan paginasi
        $messagesBySession = [];
        foreach ($sessions as $session) {
            $messagesBySession[$session->id] = Message::where('session_id', $session->id)
                                                      ->with('contact') // Eager load data kontak
                                                      ->latest('sent_at')
                                                      ->paginate(10, ['*'], 'page_session_'.$session->id);
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
