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
        Schema::create('t_fee_plan_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('fp_id');
            $table->integer('ay_id');
            $table->string('fpp_name');
            $table->float('fpp_amount');
            $table->string('fpp_late_fee');
            $table->date('fpp_due_date');
            $table->integer('fpp_month_no')->nullable();
            $table->integer('fpp_year_no')->nullable();
            $table->string('fpp_order_no')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_fee_plan_periods');
    }
};
