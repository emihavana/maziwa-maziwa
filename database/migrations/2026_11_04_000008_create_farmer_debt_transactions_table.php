<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_debt_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_id');
            $table->foreign('farmer_id')->references('farmer_id')->on('farmers')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('transaction_type');
            $table->text('reason')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_debt_transactions');
    }
};
