<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('conversation_participants')) {
            return;
        }
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('last_read_message_id')->references('id')->on('chat_messages')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};