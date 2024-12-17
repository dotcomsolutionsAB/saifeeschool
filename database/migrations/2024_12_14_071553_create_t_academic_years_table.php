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
        Schema::create('t_academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('sch_id', 10)->default('');
            $table->string('ay_name', 100)->default('');
            $table->string('ay_start_year', 100)->default('');
            $table->string('ay_start_month', 10)->default('');
            $table->string('ay_end_year', 10)->default('');
            $table->string('ay_end_month', 10)->default('');
            $table->string('ay_current', 10)->default('0');
            // $table->string('cash_in_hand', 100)->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_academic_years');
    }
};
