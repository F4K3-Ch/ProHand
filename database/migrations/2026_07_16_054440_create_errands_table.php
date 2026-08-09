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
            $table->enum('category', ['delivery', 'moving', 'cleaning', 'repair', 'grocery', 'laundry', 'project', 'other']);
            $table->text('description');
            $table->decimal('fee', 10, 2)->default(0.00);
            $table->dateTime('deadline');
            $table->string('location', 200);
            $table->text('contact');
            $table->enum('status', ['pending', 'pending_payment', 'in_process', 'pending_completion', 'pending_failure', 'dispute'])->default('pending');
            $table->boolean('cancel_requested_by_tasker')->default(false)->comment('false = normal, true = tasker wants to cancel');
            $table->boolean('cancel_requested_by_client')->default(false)->comment('false = normal, true = client wants to cancel');
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
