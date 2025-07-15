<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageSession;
use App\Models\UploadContact;
use App\Models\MessageTemplate;
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

        foreach ($contacts as $contact) {
            $text = $template->template;

            foreach ($contact->toArray() as $key => $value) {
                if (in_array($key, ['no_hp', 'nama'])) {
                    $text = str_replace('{{' . $key . '}}', $value, $text);
                }
            }

            Message::create([
                'session_id' => $session->id,
                'contact_id' => $contact->id,
                'message_text' => $text,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        $session->update([
            'status' => 'done',
            'started_at' => now(),
            'ended_at' => now()->addMinutes(49),
        ]);

        return response()->json(['message' => 'Pesan sesi ini telah dikirim.']);
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

