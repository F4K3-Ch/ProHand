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
        Schema::create('history', function (Blueprint $table) {
            $table->id();
            $table->integer('errand_id');
            $table->string('title', 150);
            $table->enum('category', ['delivery', 'moving', 'cleaning', 'repair', 'grocery', 'laundry', 'project', 'other']);
            $table->text('description')->nullable();
            $table->decimal('fee', 10, 2)->default(0.00);

            $table->string('payment_method')->nullable();
            $table->decimal('payment_amount', 10, 2)->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('payment_proof_submitted_at')->nullable();
            $table->timestamp('payment_paid_at')->nullable();
            $table->string('payment_refund_proof_path')->nullable();
            $table->timestamp('payment_refund_proof_submitted_at')->nullable();
            $table->timestamp('payment_refunded_at')->nullable();

            $table->dateTime('deadline');
            $table->string('location', 200);
            $table->text('contact');
            $table->enum('status', ['completed', 'failed']);

            $table->integer('client_id')->nullable();
            $table->string('client_last_name', 50)->nullable();
            $table->string('client_first_name', 50)->nullable();
            $table->string('client_middle_name', 50)->nullable();
            $table->string('client_email', 100)->nullable();
            $table->string('client_contact_number', 20)->nullable();
            $table->string('client_profile_picture', 255)->default('uploads/history/profile_pictures/default.jpg');

            $table->integer('tasker_id')->nullable();
            $table->string('tasker_last_name', 50)->nullable();
            $table->string('tasker_first_name', 50)->nullable();
            $table->string('tasker_middle_name', 50)->nullable();
            $table->string('tasker_email', 100)->nullable();
            $table->string('tasker_contact_number', 20)->nullable();
            $table->string('tasker_profile_picture', 255)->default('uploads/history/profile_pictures/default.jpg');

            $table->dateTime('created_at')->nullable();
            $table->dateTime('accepted_at')->nullable();
            $table->timestamp('in_progress_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->boolean('tasker_deleted')->default(false)->comment('false = visible, true = deleted by tasker');
            $table->boolean('client_deleted')->default(false)->comment('false = visible, true = deleted by client');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history');
    }
};