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
        // 1. Update status sesi menjadi 'in_progress'
        $this->session->status = 'in_progress';
        $this->session->started_at = now();
        $this->session->save();

        // 2. Ambil template pesan
        $template = MessageTemplate::where('user_id', $this->session->batch->user_id)->first();
        if (!$template) {
            $this->session->status = 'failed';
            $this->session->save();
            Log::error("Job Gagal: Template pesan untuk user #{$this->session->batch->user_id} tidak ditemukan.");
            return;
        }

        // 3. Ambil kontak untuk sesi ini
        $contactsPerSession = 100;
        $offset = ($this->session->session_number - 1) * $contactsPerSession;
        $contacts = UploadContact::where('batch_id', $this->session->batch_id)
                                 ->orderBy('id', 'asc')
                                 ->skip($offset)
                                 ->take($contactsPerSession)
                                 ->get();

        // 4. Loop dan kirim pesan melalui API WAHA
        foreach ($contacts as $contact) {
            $text = $template->template;

            // Menggabungkan data dari kolom utama dan data_json untuk personalisasi
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
                    'X-Api-Key' => 'admin', // Ganti dengan API Key Anda jika perlu
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

            // Simpan log pesan ke database
            Message::create([
                'session_id' => $this->session->id,
                'contact_id' => $contact->id,
                'message_text' => $text,
                'status' => $status,
                'sent_at' => now(),
                'waha_message_id' => $wahaMessageId,
                'from_number' => $fromNumber,
                'to_number' => $contact->no_hp,
            ]);

            // Beri jeda acak untuk menghindari blokir
            sleep(rand(6, 10));
        }

        // 5. Update status sesi menjadi 'done' setelah selesai
        $this->session->status = 'done';
        $this->session->ended_at = now();
        $this->session->save();
    }
}
