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
        Schema::create('message_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('upload_batches')->onDelete('cascade');
            $table->integer('session_number');
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_sessions');
    }
};
