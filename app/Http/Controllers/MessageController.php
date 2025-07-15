<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageSession;
use App\Models\UploadContact;
use App\Models\MessageTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function send($session_id)
    {
        $session = MessageSession::findOrFail($session_id);
        $template = MessageTemplate::where('batch_id', $session->batch_id)->first();

        if (!$template) {
            return response()->json(['message' => 'Template belum dibuat.'], 400);
        }

        $contacts = UploadContact::where('batch_id', $session->batch_id)
            ->skip(($session->session_number - 1) * 100)
            ->take(100)
            ->get();

        $session->update(['status' => 'in_progress', 'started_at' => now()]);

        $messageCount = 0;
        foreach ($contacts as $contact) {
            $text = $template->template;

            foreach ($contact->toArray() as $key => $value) {
                if (in_array($key, ['no_hp', 'nama'])) {
                    $text = str_replace('{{' . $key . '}}', $value, $text);
                }
            }

            $chatId = $contact->no_hp . '@c.us';
            $status = 'failed';

            try {
                $response = Http::withHeaders([
                    'X-Api-Key' => 'admin', // Replace with your actual WAHA API Key if required
                ])->post('http://localhost:3000/api/sendText', [
                    'session' => 'default',
                    'chatId' => $chatId,
                    'text' => $text,
                ]);

                if ($response->successful()) {
                    $status = 'sent';
                    Log::info("Message sent to {$chatId}: " . $response->body());
                } else {
                    Log::error("Failed to send message to {$chatId}: " . $response->status() . " - " . $response->body());
                }
            } catch (\Exception $e) {
                Log::error("Exception while sending message to {$chatId}: " . $e->getMessage());
            }

            Message::create([
                'session_id' => $session->id,
                'contact_id' => $contact->id,
                'message_text' => $text,
                'status' => $status,
                'sent_at' => now(),
            ]);

            $messageCount++;

            if ($messageCount % 5 == 0) {
                sleep(30);
            }
        }

        $session->update([
            'status' => 'done',
            'ended_at' => now(),
        ]);

        return response()->json(['message' => 'Pesan sesi ini telah selesai dikirim.']);
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

