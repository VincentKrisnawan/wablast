<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            // Setelah relasi dihapus, baru hapus kolomnya
            if (Schema::hasColumn('message_templates', 'batch_id')) {
                $table->dropColumn('batch_id');
            }

            // Tambahkan kolom user_id yang baru
            if (!Schema::hasColumn('message_templates', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            }
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
