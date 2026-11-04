<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmers', function (Blueprint $table) {
            $table->id();
            $table->string('farmer_id')->unique();
            $table->string('name');
            $table->string('national_id')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->decimal('debt_balance', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmers');
    }
};
