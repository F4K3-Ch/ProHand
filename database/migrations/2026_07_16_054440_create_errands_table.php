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
        Schema::create('errands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tasker_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title', 150);
            $table->enum('category', ['delivery', 'moving', 'cleaning', 'repair', 'tutoring', 'pet_care']);
            $table->text('description');
            $table->decimal('expected_hours', 5, 2);
            $table->decimal('fee', 10, 2)->default(0.00);
            $table->dateTime('deadline');
            $table->string('location', 200);
            $table->text('contact');
            $table->enum('status', ['pending', 'pending_payment', 'in_process'])->default('pending');
            $table->string('payment_receipt_path', 255)->nullable();
            $table->boolean('cancel_requested_by_tasker')->default(0)->comment('0 = normal, 1 = tasker wants to cancel');
            $table->boolean('cancel_requested_by_client')->default(0)->comment('0 = normal, 1 = client wants to cancel');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('in_process_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('errands');
    }
};
