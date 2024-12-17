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
        Schema::create('t_fee_plans', function (Blueprint $table) {
            $table->id();
            $table->integer('ay_id')->nullable();
            // $table->integer('sch_id');
            $table->text('fp_name')->nullable();
            $table->enum('fp_recurring', ['0', '1'])->default('1');
            $table->enum('fp_main_monthly_fee', ['0', '1'])->default('1');
            $table->enum('fp_main_admission_fee', ['0', '1'])->default('0');
            // $table->integer('fp_id_new')->nullable();
            $table->string('cg_id', 100)->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_fee_plans');
    }
};
