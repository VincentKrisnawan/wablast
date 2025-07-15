<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            // PERBAIKAN: Hapus foreign key constraint terlebih dahulu
            $table->dropForeign(['batch_id']);

            // Setelah relasi dihapus, baru hapus kolomnya
            $table->dropColumn('batch_id');

            // Tambahkan kolom user_id yang baru
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        });
    }

        public function down(): void
        {
            Schema::table('message_templates', function (Blueprint $table) {
                // Logika untuk mengembalikan jika diperlukan
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
                $table->foreignId('batch_id')->constrained()->onDelete('cascade');
            });
        }
    };