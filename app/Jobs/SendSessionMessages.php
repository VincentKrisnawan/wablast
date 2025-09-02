<?php

namespace App\Jobs;

use App\Models\MessageSession;
use App\Models\UploadContact;
use App\Models\Message;
use App\Models\MessageTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SendSessionMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected MessageSession $session;

    /**
     * Create a new job instance.
     */
    public function __construct(MessageSession $session)
    {
        $this->session = $session;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->session->status = 'in_progress';
        $this->session->started_at = now();
        $this->session->save();

        $template = MessageTemplate::where('user_id', $this->session->batch->user_id)->first();
        if (!$template) {
            $this->session->status = 'failed';
            $this->session->save();
            Log::error("Template pesan untuk user #{$this->session->batch->user_id} tidak ditemukan.");
            return;
        }

        $contactsPerSession = 100;
        $offset = ($this->session->session_number - 1) * $contactsPerSession;
        $contacts = UploadContact::where('batch_id', $this->session->batch_id)
            ->orderBy('id', 'asc')
            ->skip($offset)
            ->take($contactsPerSession)
            ->get();

        $apiKey   = config('waha.api_key');
        $sendUrl  = config('waha.api_url');
        $baseUrl  = config('waha.base_url');
        $sessionName = config('waha.session');

        // Ambil nomor akun (me.id) sekali
        $meId = null;
        try {
            $meResp = Http::withHeaders(['X-Api-Key' => $apiKey])
                ->get("{$baseUrl}/api/sessions/{$sessionName}/me");
            if ($meResp->successful()) {
                $meId = $meResp->json('id'); // contoh: 6285...@c.us
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengambil /me: '.$e->getMessage());
        }
        $fromNumber = self::normalizeNumber(Str::before((string)$meId, '@')); // digit-only

        foreach ($contacts as $contact) {
            $text = $template->template;
            $allData = array_merge($contact->toArray(), json_decode($contact->data_json, true) ?? []);
            foreach ($allData as $key => $value) {
                $text = str_replace('{{' . $key . '}}', (string)$value, $text);
            }

            $chatId = $contact->no_hp . '@c.us';
            $status = 'failed';
            $wahaMessageId = null;

            try {
                $response = Http::withHeaders(['X-Api-Key' => $apiKey])->post($sendUrl, [
                    'session' => $sessionName,
                    'chatId'  => $chatId,
                    'text'    => $text,
                ]);

                if ($response->successful()) {
                    $status = 'sent';
                    $data = $response->json();

                    // Ambil ID pesan lintas engine/versi
                    $wahaMessageId = data_get($data, 'id')
                        ?? data_get($data, 'id._serialized')
                        ?? data_get($data, 'message.id._serialized')
                        ?? data_get($data, 'messageId');

                    if (!$wahaMessageId) {
                        Log::warning('Tidak menemukan ID pesan di respons WAHA', ['resp' => $data]);
                    }
                } else {
                    Log::error("Gagal mengirim ke {$chatId}: " . $response->body());
                }
            } catch (\Throwable $e) {
                Log::error("Exception saat mengirim ke {$chatId}: " . $e->getMessage());
            }

            Message::create([
                'session_id'      => $this->session->id,
                'contact_id'      => $contact->id,
                'message_text'    => $text,
                'status'          => $status,
                'sent_at'         => now(),
                'waha_message_id' => $wahaMessageId,
                'from_number'     => $fromNumber, // nomor akun kita (digit-only)
                'to_number'       => self::normalizeNumber($contact->no_hp),
            ]);

            sleep(rand(6, 10));
        }

        $this->session->status = 'done';
        $this->session->ended_at = now();
        $this->session->save();
    }
    private static function normalizeNumber(?string $raw): ?string
    {
        if ($raw === null) return null;
        return preg_replace('/\D+/', '', $raw);
    }
}
