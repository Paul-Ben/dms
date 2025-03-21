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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->string('code')->unique();
            $table->string('email')->unique(); 
            $table->string('phone')->nullable(); 
            $table->enum('category', ['Ministry', 'Agency', 'Institution', 'Citizen'])->nullable();
            $table->text('address')->nullable(); 
            $table->enum('status', ['Active', 'Inactive'])->default('active'); 
            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
