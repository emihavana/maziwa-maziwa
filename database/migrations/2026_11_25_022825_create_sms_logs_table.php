<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void{
   Schema::create('sms_logs', function (Blueprint $table) {
    $table->id();
    $table->string('phone', 20);
    $table->text('message');
    $table->string('status', 10);          // 'sent' or 'failed'
    $table->text('response')->nullable();  // provider reply, for debugging
    $table->timestamps();

    $table->index(['status', 'created_at']);
});
    }
    
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};