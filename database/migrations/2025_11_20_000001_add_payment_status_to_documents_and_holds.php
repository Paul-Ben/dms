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
        Schema::table('document_holds', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->after('status');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('payment_status')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('document_holds', 'payment_status')) {
            Schema::table('document_holds', function (Blueprint $table) {
                $table->dropColumn('payment_status');
            });
        }

        if (Schema::hasColumn('documents', 'payment_status')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropColumn('payment_status');
            });
        }
    }
};