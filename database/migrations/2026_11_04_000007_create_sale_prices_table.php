<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_prices', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_per_liter', 10, 2);
            $table->date('effective_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['effective_date', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_prices');
    }
};
