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
        Schema::create('tenant_departments', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('tenant_id'); 
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name'); 
            $table->string('email')->nullable(); 
            $table->string('phone')->nullable(); 
            $table->enum('status', ['active', 'inactive'])->default('active'); 
            $table->text('description')->nullable(); 
            $table->timestamps(); 
           
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_departments');
    }
};
