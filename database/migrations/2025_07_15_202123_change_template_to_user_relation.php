<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            // Check if the column exists before trying to drop it
            if (Schema::hasColumn('message_templates', 'batch_id')) {
                // Drop the foreign key constraint first
                $table->dropForeign(['batch_id']);
                // Then drop the column
                $table->dropColumn('batch_id');
            }

            // Add the new user_id column if it doesn't exist
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
