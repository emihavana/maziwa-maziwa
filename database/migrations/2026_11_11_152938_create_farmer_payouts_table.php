<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_payouts', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_id'); // Links to your Farmer->farmer_id (e.g., F0001)
            $table->decimal('amount', 10, 2);
            $table->string('phone_number');
            $table->string('conversation_id')->nullable();          // Safaricom tracking ID
            $table->string('originator_conversation_id')->nullable(); // Used to match callbacks
            $table->string('receipt_number')->nullable();           // Official M-Pesa receipt code (e.g., QHG8...)
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('result_desc')->nullable();                // Description / error message from Safaricom
            $table->timestamps();

            // Foreign key constraint tying this payout to your farmers table
            $table->foreign('farmer_id')->references('farmer_id')->on('farmers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_payouts');
    }
};
