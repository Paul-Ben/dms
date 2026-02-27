<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('message_attachments')) {
            return;
        }
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->string('provider')->default('cloudinary');
            $table->string('public_id');
            $table->string('secure_url');
            $table->string('url')->nullable();
            $table->string('resource_type')->nullable();
            $table->string('format')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('bytes')->nullable();
            $table->string('original_name')->nullable();
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('chat_messages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};