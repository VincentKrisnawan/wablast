<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // <-- 1. Import Storage Facade

class CleanUploadData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-upload-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncates all upload-related tables and deletes uploaded files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('Apakah Anda yakin ingin menghapus SEMUA data upload DAN file Excel/CSV yang tersimpan? Ini tidak bisa dibatalkan.')) {
            
            // Nonaktifkan foreign key checks untuk sementara
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            $this->info('Mengosongkan tabel upload_contacts...');
            DB::table('upload_contacts')->truncate();

            $this->info('Mengosongkan tabel message_sessions...');
            DB::table('message_sessions')->truncate();

            $this->info('Mengosongkan tabel message_templates...');
            DB::table('message_templates')->truncate();

            $this->info('Mengosongkan tabel upload_batches...');
            DB::table('upload_batches')->truncate();

            // Aktifkan kembali foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->info('Semua tabel berhasil dikosongkan.');

            // 2. Hapus file-file yang sudah di-upload
            $this->info('Menghapus file di storage/app/public/uploads...');
            Storage::disk('public')->deleteDirectory('uploads');
            Storage::disk('public')->makeDirectory('uploads'); // Buat kembali folder agar upload berikutnya tidak error
            $this->info('Folder uploads berhasil dibersihkan.');

            $this->info('Semua data dan file upload berhasil dihapus.');
        } else {
            $this->info('Operasi dibatalkan.');
        }

        return 0;
    }
}
