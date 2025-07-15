<?php

namespace App\Http\Controllers;

use App\Models\UploadBatch;
use App\Models\UploadContact;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class UploadContactController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx',
        ]);

        $file = $request->file('file');
        $filename = time() . '_' . $file->getClientOriginalName();

        // Simpan batch upload
        $batch = UploadBatch::create([
            'user_id' => auth()->id() ?? 1, // sementara hardcoded user_id 1
            'filename' => $filename,
            'total_contacts' => 0,
            'uploaded_at' => now(),
        ]);

        $rows = Excel::toArray([], $file)[0];

        // Ambil header
        $header = array_map(fn($h) => Str::slug($h, '_'), $rows[0]);

        unset($rows[0]); // hapus header dari rows

        $total = 0;
        foreach ($rows as $row) {
            $assoc = array_combine($header, $row);

            if (isset($assoc['no_hp']) && isset($assoc['nama'])) {
                UploadContact::create([
                    'batch_id' => $batch->id,
                    'no_hp' => $assoc['no_hp'],
                    'nama' => $assoc['nama'],
                    'data_json' => collect($assoc)->except(['no_hp', 'nama']),
                ]);
                $total++;
            }
        }

        // update jumlah kontak
        $batch->update(['total_contacts' => $total]);

        return response()->json([
            'message' => 'Upload berhasil',
            'batch_id' => $batch->id,
            'total' => $total
        ]);
    }
}

