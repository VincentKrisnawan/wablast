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

        $event = $payload['event'] ?? null;
        if ($event === 'message.ack') {
            $this->handleMessageAck($payload);
        } elseif ($event === 'message') {
            $this->handleIncomingMessage($payload);
        }

        return response()->json(['status' => 'success']);
    }

    protected function handleMessageAck(array $envelope) : void
    {
        Log::info('Handling message.ack event.', $envelope);

        $ack = $envelope['payload'] ?? [];
        $rawId = $ack['id'] ?? null;
        $wahaMessageId = is_array($rawId) ? ($rawId['_serialized'] ?? null) : $rawId;
        $ackName = $ack['ackName'] ?? null;

        if (!$wahaMessageId || !$ackName) {
            Log::warning('Missing WAHA Message ID or ACK Name in payload.', ['id' => $wahaMessageId, 'ackName' => $ackName]);
            return;
        }

        $message = Message::where('waha_message_id', $wahaMessageId)->first();

        // Fallback korelasi: isi balik ID bila belum tersimpan
        if (!$message) {
            $mine = self::normalizeNumber(Str::before($envelope['me']['id'] ?? '', '@'));
            $peer = self::normalizeNumber(Str::before($ack['from'] ?? '', '@'));

            if ($mine && $peer) {
                $message = Message::whereNull('waha_message_id')
                    ->where('from_number', $mine)
                    ->where('to_number', $peer)
                    ->where('status', 'sent')
                    ->where('sent_at', '>=', now()->subMinutes(30))
                    ->latest('sent_at')
                    ->first();

                if ($message) {
                    $message->waha_message_id = $wahaMessageId;
                    $message->save();
                }
            }
        }

        if (!$message) {
            Log::warning('Message not found for WAHA ID.', ['wahaMessageId' => $wahaMessageId]);
            return;
        }

        // Mapping ACK → status dengan prioritas
        $map = [
            'SERVER' => 'sent',
            'DEVICE' => 'sent',
            'READ'   => 'read',
            'PLAYED' => 'read',
            'ERROR'  => 'failed',
        ];
        $prio = ['failed' => 0, 'sent' => 1, 'read' => 2, 'replied' => 3];

        $new = $map[$ackName] ?? null;
        if ($new) {
            $curr = $message->status ?? '';
            if (($prio[$new] ?? -1) > ($prio[$curr] ?? -1)) {
                $message->status = $new;
                if ($new === 'read' && !$message->read_at) {
                    $message->read_at = now();
                }
                $message->save();
                Log::info('Message status updated via ACK.', ['id' => $message->id, 'ackName' => $ackName]);
            }
        }
    }

    protected function handleIncomingMessage(array $envelope): void
    {
        Log::info('Handling incoming message event.', $envelope);

        $p = $envelope['payload'] ?? [];
        $from = $p['from'] ?? null;
        $isFromMe = (bool)($p['fromMe'] ?? false);

        // NOWEB bisa tidak mengirim payload.to; gunakan me.id sebagai nomor kita
        $to = $p['to'] ?? ($envelope['me']['id'] ?? null);

        if ($isFromMe || !$from || !$to) {
            Log::warning('Skip incoming: missing from/to or fromMe=true', ['from' => $from, 'to' => $to, 'fromMe' => $isFromMe]);
            return;
        }

        $cleanFrom = self::normalizeNumber(Str::before($from, '@')); // lawan bicara
        $cleanTo   = self::normalizeNumber(Str::before($to, '@'));   // nomor kita

        $outgoing = Message::where('to_number', $cleanFrom)
            ->where('from_number', $cleanTo)
            ->whereIn('status', ['sent', 'read'])
            ->latest('sent_at')
            ->first();

        if ($outgoing) {
            // Jangan turunkan status read → replied? replied > read (prioritas lebih tinggi)
            $outgoing->status = 'replied';
            if (!$outgoing->replied_at) $outgoing->replied_at = now();
            $outgoing->save();
            Log::info('Outgoing message status updated to replied.', ['message_id' => $outgoing->id]);
        } else {
            Log::info('No matching outgoing message found for incoming message.', [
                'webhook_from' => $from,
                'webhook_to'   => $to,
                'body'         => $p['body'] ?? '',
            ]);
        }
    }


    /**
     * PERBAIKAN: Method ini sekarang berisi logika yang benar untuk menampilkan dashboard.
     */
    public function dashboard(Request $request)
    {
       $user = Auth::user();

    // Basis query untuk session & message
    $sessionBase = MessageSession::query();
    $messageBase = Message::query();

    // Scope by user (non-admin hanya lihat miliknya)
    if ($user->role !== 'admin') {
        $sessionBase->whereHas('batch', fn ($q) => $q->where('user_id', $user->id));
        $messageBase->whereHas('session.batch', fn ($q) => $q->where('user_id', $user->id));
    }

    // Ambil daftar session id yang relevan
    $sessionIds = (clone $sessionBase)->pluck('id');

    // --- 1) Hitung total/sent/read/replied dengan 1 query agregasi (hemat & akurat)
    // SUM(CASE WHEN ...) adalah pola "conditional aggregates" yang resmi & efisien.
    // Lihat: Reinink – Conditional aggregates.
    $stats = (clone $messageBase)
        ->whereIn('session_id', $sessionIds)
        ->selectRaw('
            COUNT(*)                                     AS total,
            SUM(CASE WHEN status = "sent"    THEN 1 ELSE 0 END) AS sent,
            SUM(CASE WHEN status = "read"    THEN 1 ELSE 0 END) AS `read`,
            SUM(CASE WHEN status = "replied" THEN 1 ELSE 0 END) AS replied
        ')
        ->first();

    $totalMessages   = (int) ($stats->total   ?? 0);
    $sentMessages    = (int) ($stats->sent    ?? 0);
    $readMessages    = (int) ($stats->read    ?? 0);
    $repliedMessages = (int) ($stats->replied ?? 0);

    // --- 2) Ambil daftar session + counter per-status tanpa N+1
    // Gunakan withCount() bersyarat untuk load jumlah pesan per-session.
    // Ini cara idiomatik Laravel untuk "count relasi dengan kondisi".
    $sessions = (clone $sessionBase)
        ->with('batch.user')
        ->withCount([
            'messages as messages_total_count',
            'messages as messages_sent_count' => fn ($q) => $q->where('status', 'sent'),
            'messages as messages_read_count' => fn ($q) => $q->where('status', 'read'),
            'messages as messages_replied_count' => fn ($q) => $q->where('status', 'replied'),
        ])
        ->orderBy('session_number', 'asc')
        ->paginate(5);

    // Pilih session aktif
    $activeSessionId = $request->query('session', $sessions->first()->id ?? null);

    // --- 3) Pesan per-session (tetap dipaginasi per session)
    $messagesBySession = [];
    foreach ($sessions as $session) {
        $messagesBySession[$session->id] = Message::where('session_id', $session->id)
            ->with('contact')
            ->latest('sent_at')
            ->paginate(10, ['*'], 'page_session_' . $session->id);
    }

    return view('pages.dashboard', [
        'totalMessages'   => $totalMessages,
        'sentMessages'    => $sentMessages,
        'readMessages'    => $readMessages,
        'repliedMessages' => $repliedMessages,
        'sessions'        => $sessions,
        'messages'        => $messagesBySession,
        'activeSession'   => $activeSessionId,
    ]);
    }

    public function sessionDetails($sessionId)
    {
        $session = MessageSession::with(['messages.contact'])->findOrFail($sessionId);
        return view('pages.session_details', compact('session'));
    }
    private static function normalizeNumber(?string $raw): ?string
    {
        if ($raw === null) return null;
        return preg_replace('/\D+/', '', $raw);
    }
}
