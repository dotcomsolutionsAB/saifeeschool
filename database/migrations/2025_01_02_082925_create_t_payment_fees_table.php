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
        Schema::create('t_payment_fees', function (Blueprint $table) {
            $table->id();
            $table->string('st_id');
            $table->string('order_id');
            $table->integer('against_fees')->comment('Store F_id');
            $table->enum('order_status', ['0', '1'])->default('0')->comment('0: Not cleared, 1: Cleared');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_payment_fees');
    }
};
