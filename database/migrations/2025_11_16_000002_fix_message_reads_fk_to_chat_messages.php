<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_reads', function (Blueprint $table) {
            // Drop incorrect FK to messages
            $table->dropForeign(['message_id']);
        });

        Schema::table('message_reads', function (Blueprint $table) {
            // Re-add FK pointing to chat_messages
            $table->foreign('message_id')
                ->references('id')
                ->on('chat_messages')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_reads', function (Blueprint $table) {
            $table->dropForeign(['message_id']);
        });

        Schema::table('message_reads', function (Blueprint $table) {
            $table->foreign('message_id')
                ->references('id')
                ->on('messages')
                ->onDelete('cascade');
        });
    }
};