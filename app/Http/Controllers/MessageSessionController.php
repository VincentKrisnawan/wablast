<?php

namespace App\Http\Controllers;

use App\Models\MessageSession;
use App\Models\UploadBatch;
use App\Models\UploadContact;
use Illuminate\Http\Request;

class MessageSessionController extends Controller
{
    // Membuat sesi otomatis dari upload batch
    public function generateSessions($batch_id)
    {
        $batch = UploadBatch::findOrFail($batch_id);

        // Ambil semua kontak dari batch
        $contacts = UploadContact::where('batch_id', $batch_id)->get();

        if ($contacts->isEmpty()) {
            return response()->json(['message' => 'Tidak ada kontak ditemukan'], 404);
        }

        $chunks = $contacts->chunk(100);
        $sesiKe = 1;

        foreach ($chunks as $chunk) {
            MessageSession::create([
                'batch_id' => $batch_id,
                'session_number' => $sesiKe,
                'status' => 'pending',
            ]);
            $sesiKe++;
        }

        return response()->json([
            'message' => 'Sesi berhasil dibuat',
            'total_sessions' => $chunks->count(),
        ]);
    }

    // Menampilkan semua sesi dari satu batch upload
    public function getSessions($batch_id)
    {
        $sessions = MessageSession::where('batch_id', $batch_id)->get();

        return response()->json([
            'data' => $sessions
        ]);
    }
}

