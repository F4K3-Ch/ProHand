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
        Schema::create('history_payment_proofs', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('history_errand_id')->constrained('history')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_payment_proofs');
    }
};
