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
        Schema::create('t_counter', function (Blueprint $table) {
            $table->id();
            $table->string('t_name')->unique(); // Name of the table to associate the counter
            $table->integer('number')->default(0); // Counter for the serial numbers
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_counter');
    }
};
