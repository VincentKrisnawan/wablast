<?php

namespace App\Jobs;

use App\Models\MessageSession;
use App\Models\UploadContact;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendSessionImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected MessageSession $session,
        protected array $filePayload,          // ['url'=>...] atau ['data'=>..., 'mimetype'=>..., 'filename'=>...]
        protected string $caption
    ) {}

    public function handle(): void
    {
        $this->session->update(['status' => 'in_progress', 'started_at' => now()]);

        // Ambil 100 kontak per sesi (meniru logika kamu)
        $contactsPerSession = 100;
        $offset = ($this->session->session_number - 1) * $contactsPerSession;

        $contacts = UploadContact::where('batch_id', $this->session->batch_id)
            ->orderBy('id', 'asc')
            ->skip($offset)->take($contactsPerSession)->get();

        $apiKey      = config('waha.api_key');
        $sendImage   = config('waha.api_url_image'); // /api/sendImage
        $sessionName = config('waha.session');

        // opsional: ambil nomor akun utk from_number (stabil lintas engine)
        $fromNumber = null;
        try {
            $meResp = Http::withHeaders(['X-Api-Key' => $apiKey])
                ->get(config('waha.base_url') ."/api/sessions/{$sessionName}/me");
            if ($meResp->successful()) {
                $fromNumber = self::normalize(Str::before($meResp->json('id'), '@'));
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal memanggil /me: '.$e->getMessage());
        }

        foreach ($contacts as $contact) {
            $chatId = $contact->no_hp . '@c.us';
            $status = 'failed';
            $wahaMessageId = null;

            $body = [
                'session' => $sessionName,
                'chatId'  => $chatId,
                'caption' => $this->caption, // WAHA mendukung caption di sendImage
                'file'    => $this->filePayload,
            ];

            try {
                $resp = Http::withHeaders(['X-Api-Key' => $apiKey])->post($sendImage, $body);

                if ($resp->successful()) {
                    $status = 'sent';
                    $data = $resp->json();

                    // Ambil ID pesan secara defensif (lintas engine/versi)
                    $wahaMessageId = data_get($data, 'id')
                        ?? data_get($data, 'id._serialized')
                        ?? data_get($data, 'message.id._serialized')
                        ?? data_get($data, 'messageId');
                } else {
                    Log::error("sendImage gagal ke {$chatId}: ".$resp->body());
                }
            } catch (\Throwable $e) {
                Log::error("Exception sendImage ke {$chatId}: ".$e->getMessage());
            }

            Message::create([
                'session_id'      => $this->session->id,
                'contact_id'      => $contact->id,
                'message_text'    => $this->caption,                   // simpan caption
                'status'          => $status,
                'sent_at'         => now(),
                'waha_message_id' => $wahaMessageId,
                'from_number'     => $fromNumber,
                'to_number'       => self::normalize($contact->no_hp),
            ]);

            sleep(rand(6, 10));
        }

        $this->session->update(['status' => 'done', 'ended_at' => now()]);
    }

    private static function normalize(?string $raw): ?string
    {
        return $raw ? preg_replace('/\D+/', '', $raw) : null;
    }
}
