<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageSession;
use App\Models\UploadContact;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class MessageController extends Controller
{
    public function send(MessageSession $session)
    {
        try {
            if ($session->batch->user_id !== Auth::id()) {
                return response()->json(['message' => 'Aksi tidak diizinkan.'], 403);
            }

            $template = MessageTemplate::where('user_id', Auth::id())->first();

            if (!$template) {
                return response()->json(['message' => 'Template belum dibuat.'], 400);
            }

            $contacts = UploadContact::where('batch_id', $session->batch_id)
                ->skip(($session->session_number - 1) * 100)
                ->take(100)
                ->get();

            $session->update(['status' => 'in_progress', 'started_at' => now()]);

            foreach ($contacts as $contact) {
                $text = $template->template;

                $allData = array_merge($contact->toArray(), json_decode($contact->data_json, true) ?? []);
                foreach ($allData as $key => $value) {
                    $text = str_replace('{{' . $key . '}}', $value, $text);
                }

                $chatId = $contact->no_hp . '@c.us';
                $status = 'failed';
                $wahaMessageId = null;
                $fromNumber = null;

                try {
                    $response = Http::withHeaders([
                        'X-Api-Key' => 'admin',
                    ])->post('http://localhost:3000/api/sendText', [
                        'session' => 'default',
                        'chatId' => $chatId,
                        'text' => $text,
                    ]);

                    if ($response->successful()) {
                        $status = 'sent';
                        $responseData = $response->json();
                        $wahaMessageId = $responseData['id']['_serialized'] ?? null;
                        $fromNumber = $responseData['_data']['from']['_serialized'] ?? null;
                    } else {
                        Log::error("Gagal mengirim ke {$chatId}: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::error("Exception saat mengirim ke {$chatId}: " . $e->getMessage());
                }

                Message::create([
                    'session_id' => $session->id,
                    'contact_id' => $contact->id,
                    'message_text' => $text,
                    'status' => $status,
                    'sent_at' => now(),
                    'waha_message_id' => $wahaMessageId,
                    'from_number' => $fromNumber,
                    'to_number' => $contact->no_hp,
                ]);

                sleep(rand(6, 10));
            }

            $session->update(['status' => 'done', 'ended_at' => now()]);

            return response()->json(['message' => 'Pesan sesi ini telah selesai dikirim.']);
        } catch (\Exception $e) {
            Log::error("Error in MessageController@send: " . $e->getMessage());
            return response()->json(['message' => 'Terjadi kesalahan pada server.', 'error' => $e->getMessage()], 500);
        }
    }

    public function report($session_id)
    {
        $total = Message::where('session_id', $session_id)->count();
        $sent = Message::where('session_id', $session_id)->where('status', 'sent')->count();
        $read = Message::where('session_id', $session_id)->where('status', 'read')->count();
        $replied = Message::where('session_id', $session_id)->where('status', 'replied')->count();

        return response()->json([
            'total' => $total,
            'sent' => $sent,
            'read' => $read,
            'replied' => $replied
        ]);
    }
}

