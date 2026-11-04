<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milk_collections', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_id');
            $table->foreign('farmer_id')->references('farmer_id')->on('farmers')->cascadeOnDelete();
            $table->foreignId('collector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->decimal('qty', 10, 2);
            $table->date('collection_date');
            $table->boolean('is_correction')->default(false);
            $table->text('correction_reason')->nullable();
            $table->foreignId('corrected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milk_collections');
    }
};
