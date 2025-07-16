<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\MessageSession;
use App\Models\UploadContact;
use Illuminate\Support\Facades\Log;
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
        $wahaMessageId = $payload['payload']['id'] ?? null;
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
                        // No need to update sent_at again if it's already set
                        break;
                    case 'READ':
                        $message->status = 'read';
                        $message->read_at = now();
                        break;
                    case 'PLAYED':
                        $message->status = 'read'; // WAHA considers played as read
                        $message->read_at = now();
                        break;
                    case 'REPLIED': // Assuming 'REPLIED' is a possible ackName or custom event
                        $message->status = 'replied';
                        $message->replied_at = now();
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
        $wahaMessageId = $payload['payload']['id'] ?? null;
        $from = $payload['payload']['from'] ?? null;
        $to = $payload['payload']['to'] ?? null;
        $body = $payload['payload']['body'] ?? null;

        Log::info("Incoming Message: WAHA ID: {$wahaMessageId}, From: {$from}, To: {$to}");

        // Ensure numbers are in a consistent format (e.g., remove @c.us for comparison if needed)
        $cleanFrom = Str::before($from, '@');
        $cleanTo = Str::before($to, '@');

        $outgoingMessage = Message::where('to_number', $cleanFrom)
                                  ->where('from_number', $cleanTo)
                                  ->whereIn('status', ['sent', 'read']) // Allow replies to messages that are sent or read
                                  ->orderBy('sent_at', 'desc')
                                  ->first();

        if ($outgoingMessage) {
            Log::info('Matching outgoing message found for reply.', ['message_id' => $outgoingMessage->id, 'db_from' => $outgoingMessage->from_number, 'db_to' => $outgoingMessage->to_number, 'webhook_from' => $from, 'webhook_to' => $to]);
            $outgoingMessage->status = 'replied';
            $outgoingMessage->replied_at = now();
            $outgoingMessage->save();
            Log::info('Outgoing message status updated to replied.', ['message_id' => $outgoingMessage->id]);
        } else {
            Log::info('No matching outgoing message found for incoming message.', ['webhook_from' => $from, 'webhook_to' => $to, 'body' => $body]);
        }
    }

    public function dashboard(Request $request)
    {
        $totalMessages = Message::count();
        $sentMessages = Message::where('status', 'sent')->count();
        $readMessages = Message::where('status', 'read')->count();
        $repliedMessages = Message::where('status', 'replied')->count();

        $sessions = MessageSession::all();
        $messages = [];

        $activeSession = $request->query('session');

        foreach ($sessions as $session) {
            $messages[$session->id] = Message::where('session_id', $session->id)
                ->with('contact')
                ->paginate(10, ['*'], 'page_' . $session->id)
                ->withQueryString();
        }

        return view('pages.dashboard', compact(
            'totalMessages', 'sentMessages', 'readMessages', 'repliedMessages', 'sessions', 'messages', 'activeSession'
        ));
    }

    public function sessionDetails($sessionId)
    {
        $session = MessageSession::with(['messages.contact'])->findOrFail($sessionId);

        return view('pages.session_details', compact('session'));
    }
}
