<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

// Import class dari PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

// Import Model Anda
use App\Models\UploadBatch;
use App\Models\UploadContact;
use App\Models\MessageSession;
use App\Models\MessageTemplate;

class HomeController extends Controller
{
    /**
     * Menampilkan halaman utama (dashboard).
     */
    public function index()
    {
        // Logika untuk batch dan sesi tidak berubah
        $latestBatch = UploadBatch::where('user_id', Auth::id())->latest()->first();
        $sessions = $latestBatch ? $latestBatch->sessions()->withCount(['messages' => function ($query) {
            $query->where('status', 'sent');
        }])->get() : [];

        // PERBAIKAN: Ambil template berdasarkan user yang login, bukan batch
        $template = MessageTemplate::where('user_id', Auth::id())->first();
        $templateText = $template ? $template->template : '';

        return view('pages.home', [ 
            'sessions' => $sessions,
            'latest_batch' => $latestBatch,
            'latest_batch_id' => $latestBatch ? $latestBatch->id : null,
            'template_text' => $templateText, // Kirim template yang persisten
        ]);
    }

    /**
     * Menyimpan template pesan ke database.
     */
    public function storeTemplate(Request $request)
    {
        // PERBAIKAN: Validasi hanya teks, karena kita akan menggunakan ID user yang login
        $request->validate([
            'template_text' => 'required|string|min:10',
        ]);

        // PERBAIKAN: Simpan atau perbarui template berdasarkan user_id
        MessageTemplate::updateOrCreate(
            ['user_id' => Auth::id()], // Kunci untuk mencari record
            ['template' => $request->template_text] // Data untuk diupdate atau dibuat
        );

        return back()->with('success', 'Template pesan berhasil disimpan!');
    }

    // ... (Sisa method lainnya seperti upload, destroySession, dll. tidak berubah)
    
    public function upload(Request $request)
    {
        $request->validate([
            'file_kontak' => 'required|mimes:csv,xlsx,xls',
        ]);

        DB::beginTransaction();

        try {
            $file = $request->file('file_kontak');
            
            $batch = UploadBatch::where('user_id', Auth::id())->latest()->first();

            if (!$batch) {
                $batch = UploadBatch::create([
                    'user_id'        => Auth::id(),
                    'filename'       => 'Batch Gabungan',
                    'total_contacts' => 0,
                ]);
            }

            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            
            $headerRow = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1', null, true, false)[0];
            $headers = array_map(fn($h) => strtolower(str_replace(' ', '_', trim($h))), $headerRow);

            $noHpIndex = array_search('no_hp', $headers);
            $namaIndex = array_search('nama', $headers);

            if ($noHpIndex === false || $namaIndex === false) {
                throw new \Exception("File yang diupload harus memiliki kolom 'no_hp' dan 'nama'.");
            }

            $highestRow = $sheet->getHighestRow();
            $newContactsCount = 0;

            for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
                $rowData = $sheet->rangeToArray('A' . $rowIndex . ':' . $sheet->getHighestColumn() . $rowIndex, null, true, false)[0];
                $no_hp = $rowData[$noHpIndex] ?? null;
                $nama = $rowData[$namaIndex] ?? null;

                if (empty($no_hp) || empty($nama)) continue;

                $data_json = [];
                foreach ($headers as $index => $headerName) {
                    if ($index !== $noHpIndex && $index !== $namaIndex) {
                        $data_json[$headerName] = $rowData[$index] ?? null;
                    }
                }

                UploadContact::create([
                    'batch_id'  => $batch->id,
                    'no_hp'     => $no_hp,
                    'nama'      => $nama,
                    'data_json' => json_encode($data_json),
                ]);
                $newContactsCount++;
            }

            if ($newContactsCount === 0) {
                throw new \Exception("File yang diupload tidak berisi data kontak yang valid.");
            }

            $batch->total_contacts += $newContactsCount;
            $batch->save();

            MessageSession::where('batch_id', $batch->id)->delete();

            $sessionCount = ceil($batch->total_contacts / 100);
            for ($i = 1; $i <= $sessionCount; $i++) {
                MessageSession::create([
                    'batch_id'       => $batch->id,
                    'session_number' => $i,
                    'status'         => 'pending',
                ]);
            }

            DB::commit();
            return redirect()->route('home')->with('success', 'Kontak baru berhasil ditambahkan dan sesi telah diperbarui!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function showAllContacts()
    {
        $contacts = UploadContact::whereHas('batch', function ($query) {
            $query->where('user_id', Auth::id());
        })
        ->orderBy('id', 'desc')
        ->paginate(10);

        return view('pages.lihat-data', [
            'contacts' => $contacts,
        ]);
    }

    public function destroySession(MessageSession $session)
    {
        if ($session->batch->user_id !== Auth::id()) {
            return back()->with('error', 'Anda tidak diizinkan menghapus sesi ini.');
        }

        DB::beginTransaction();

        try {
            $contactsPerSession = 100;
            $offset = ($session->session_number - 1) * $contactsPerSession;

            $contactIdsToDelete = UploadContact::where('batch_id', $session->batch_id)
                                              ->orderBy('id', 'asc')
                                              ->skip($offset)
                                              ->take($contactsPerSession)
                                              ->pluck('id');
            
            $deletedCount = 0;
            if ($contactIdsToDelete->isNotEmpty()) {
                $deletedCount = UploadContact::whereIn('id', $contactIdsToDelete)->delete();
            }

            $session->delete();

            $batch = $session->batch;
            $batch->total_contacts -= $deletedCount;
            $batch->save();

            DB::commit();

            return redirect()->route('home')->with('success', 'Sesi dan kontaknya berhasil dihapus.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus sesi: ' . $e->getMessage());
        }
    }

    public function cleanup()
    {
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('upload_contacts')->truncate();
            DB::table('message_sessions')->truncate();
            DB::table('message_templates')->truncate();
            DB::table('upload_batches')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            Storage::disk('public')->deleteDirectory('uploads');
            Storage::disk('public')->makeDirectory('uploads');

            return redirect()->route('home')->with('success', 'Semua data dan file berhasil dibersihkan.');

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membersihkan data: ' . $e->getMessage());
        }
    }
}
