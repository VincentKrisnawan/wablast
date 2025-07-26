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
use App\Models\Message;

use App\Jobs\SendSessionMessages;

class HomeController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        
        // PERBAIKAN: Hapus pengecualian untuk admin.
        // Sekarang, halaman ini akan selalu menampilkan sesi milik pengguna yang sedang login,
        // baik itu user biasa maupun admin.
        $sessions = MessageSession::whereHas('batch', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->with('batch.user')
        ->withCount('messages')
        ->latest('id')
        ->paginate(10);

        $template = MessageTemplate::where('user_id', $user->id)->first();
        $templateText = $template ? $template->template : '';
        
        return view('pages.home', [ 
            'sessions' => $sessions,
            'template_text' => $templateText,
        ]);
    }

    /**
     * Menangani proses upload file kontak dengan logika penggabungan sesi yang cerdas.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file_kontak' => 'required|mimes:csv,xlsx,xls',
        ]);

        DB::beginTransaction();

        try {
            $file = $request->file('file_kontak');
            
            $latestBatch = UploadBatch::where('user_id', Auth::id())->latest()->first();
            $createNewBatch = false;

            // 1. Periksa batch terakhir, jika ada.
            if ($latestBatch) {
                // Cek apakah ada sesi di batch terakhir yang statusnya BUKAN 'pending'.
                $hasNonPendingSession = $latestBatch->sessions()->where('status', '!=', 'pending')->exists();
                if ($hasNonPendingSession) {
                    $createNewBatch = true;
                }
            }

            // Baca file Excel terlebih dahulu untuk mendapatkan jumlah kontak baru
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
            $newContacts = [];
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
                $newContacts[] = ['no_hp' => $no_hp, 'nama' => $nama, 'data_json' => json_encode($data_json)];
            }

            if (empty($newContacts)) {
                throw new \Exception("File yang diupload tidak berisi data kontak yang valid.");
            }

            // 2. Tentukan apakah kita akan membuat batch baru atau menggunakan yang lama.
            if ($createNewBatch || !$latestBatch) {
                $batch = UploadBatch::create(['user_id' => Auth::id(), 'filename' => 'Batch - ' . now()->format('d M Y, H:i'), 'total_contacts' => 0]);
                $successMessage = 'Batch baru berhasil dibuat dan sesi telah dibuat!';
                $oldSessionCount = 0;
            } else {
                $batch = $latestBatch;
                $successMessage = 'Kontak baru berhasil ditambahkan ke batch terakhir!';
                $oldSessionCount = $batch->sessions()->count();
            }

            // 3. Tambahkan kontak baru ke database
            foreach ($newContacts as $contact) {
                UploadContact::create(array_merge($contact, ['batch_id' => $batch->id]));
            }

            // 4. Perbarui total kontak di batch
            $batch->total_contacts += count($newContacts);
            $batch->save();
            
            // 5. Buat HANYA sesi tambahan yang diperlukan
            $newSessionCount = ceil($batch->total_contacts / 100);
            for ($i = $oldSessionCount + 1; $i <= $newSessionCount; $i++) {
                MessageSession::create([
                    'batch_id'       => $batch->id,
                    'session_number' => $i,
                    'status'         => 'pending',
                ]);
            }

            DB::commit();
            return redirect()->route('home')->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // ... (sisa method lainnya tidak berubah)
    public function showAllContacts()
    {
        $user = Auth::user();
        $query = UploadContact::query();

        // Jika user BUKAN admin, filter kontak hanya untuk user tersebut.
        if ($user->role !== 'admin') {
            $query->whereHas('batch', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Admin akan melihat semua kontak dari semua user.
        $contacts = $query->with('batch.user') // Eager load relasi user untuk menampilkan email
                          ->orderBy('id', 'desc')
                          ->paginate(10);

        return view('pages.lihat-data', [
            'contacts' => $contacts,
        ]);
    }

    public function destroySession(MessageSession $session)
    {
        // HANYA ADMIN YANG BISA MENGHAPUS
        $user = Auth::user();

        // PERBAIKAN: Izinkan penghapusan jika user adalah admin ATAU pemilik sesi.
        if ($user->role !== 'admin' && $session->batch->user_id !== $user->id) {
            return back()->with('error', 'Aksi tidak diizinkan.');
        }

        // Logika penghapusan tetap sama
        DB::beginTransaction();
        try {
            $contactsPerSession = 100;
            $sessionNumbersInBatch = MessageSession::where('batch_id', $session->batch_id)->orderBy('session_number', 'asc')->pluck('session_number')->toArray();
            $localIndex = array_search($session->session_number, $sessionNumbersInBatch);

            if ($localIndex === false) {
                throw new \Exception("Sesi tidak ditemukan di dalam batch-nya.");
            }

            $offset = $localIndex * $contactsPerSession;
            $contactIdsToDelete = UploadContact::where('batch_id', $session->batch_id)->orderBy('id', 'asc')->skip($offset)->take($contactsPerSession)->pluck('id');
            
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

    public function storeTemplate(Request $request)
    {
        $request->validate([
            'template_text' => 'required|string|min:10',
        ]);

        MessageTemplate::updateOrCreate(
            ['user_id' => Auth::id()],
            ['template' => $request->template_text]
        );

        return back()->with('success', 'Template pesan berhasil disimpan!');
    }

    public function cleanup()
    {
        // HANYA ADMIN YANG BISA MERESET
        if (Auth::user()->role !== 'admin') {
            return back()->with('error', 'Aksi tidak diizinkan.');
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('upload_contacts')->truncate();
            DB::table('message_sessions')->truncate();
            DB::table('message_templates')->truncate();
            DB::table('upload_batches')->truncate();
            DB::table('messages')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            Storage::disk('public')->deleteDirectory('uploads');
            Storage::disk('public')->makeDirectory('uploads');

            return redirect()->route('home')->with('success', 'Semua data dan file berhasil dibersihkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membersihkan data: ' . $e->getMessage());
        }
    }

    public function sendSession(MessageSession $session)
    {
        // Otorisasi: Pastikan user hanya bisa mengirim sesi miliknya
        if ($session->batch->user_id !== Auth::id()) {
            return response()->json(['message' => 'Aksi tidak diizinkan.'], 403);
        }

        // Pastikan template pesan ada sebelum memulai
        $templateExists = MessageTemplate::where('user_id', Auth::id())->exists();
        if (!$templateExists) {
            return response()->json(['message' => 'Template pesan belum diatur. Silakan simpan template terlebih dahulu.'], 422);
        }

        // Kirim tugas ke antrian
        SendSessionMessages::dispatch($session);

        // Beri respons langsung ke browser
        return response()->json(['message' => 'Proses pengiriman untuk Sesi #' . $session->session_number . ' telah dimulai.']);
    }

    public function getSessionStatus(MessageSession $session)
    {
        // Otorisasi: Pastikan user hanya bisa memeriksa sesi miliknya.
        if ($session->batch->user_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Hitung jumlah pesan yang sudah terkirim untuk sesi ini.
        $sentCount = Message::where('session_id', $session->id)
                            ->where('status', 'sent')
                            ->count();

        // Kembalikan status dan jumlah terkirim sebagai JSON.
        return response()->json([
            'status' => $session->status,
            'sent_count' => $sentCount,
        ]);
    }

    
}
