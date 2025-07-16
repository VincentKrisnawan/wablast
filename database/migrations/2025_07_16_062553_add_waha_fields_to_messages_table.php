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
            $table->string('waha_message_id')->nullable()->after('id');
            $table->string('from_number')->nullable()->after('waha_message_id');
            $table->string('to_number')->nullable()->after('from_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['waha_message_id', 'from_number', 'to_number']);
        });
    }
};
