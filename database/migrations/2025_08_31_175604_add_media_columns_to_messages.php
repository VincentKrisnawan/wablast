<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->enum('message_type', ['text', 'image'])->default('text')->after('message_text');

            $table->string('media_url', 1024)->nullable()->after('message_type');
            $table->string('media_mime', 100)->nullable()->after('media_url');
            $table->string('media_filename', 255)->nullable()->after('media_mime');
            $table->enum('media_source', ['url', 'base64'])->nullable()->after('media_filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['message_type', 'media_url', 'media_mime', 'media_filename', 'media_source']);
        });
    }
};
