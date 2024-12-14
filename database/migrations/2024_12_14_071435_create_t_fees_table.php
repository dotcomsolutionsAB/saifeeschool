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
        Schema::create('t_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('st_id')->nullable();
            $table->string('st_roll_no', 100)->default('');
            $table->unsignedInteger('sch_id')->default(1);
            $table->unsignedInteger('fpp_id')->nullable();
            $table->unsignedInteger('fp_id')->nullable();
            $table->string('cg_id', 10)->default('');
            $table->unsignedInteger('ay_id')->nullable();
            $table->text('fpp_name')->nullable();
            $table->unsignedInteger('fpp_due_date')->nullable();
            $table->unsignedInteger('fpp_month_no')->nullable();
            $table->unsignedInteger('fpp_year_no')->nullable();
            $table->unsignedInteger('fpp_order_no')->nullable();
            $table->float('fpp_amount', 10, 2)->default(0.00);
            $table->float('f_concession', 10, 2)->default(0.00);
            $table->float('fpp_late_fee', 10, 2)->default(0.00);
            $table->enum('f_late_fee_applicable', ['0', '1'])->nullable();
            $table->float('f_late_fee_paid', 10, 2)->default(0.00);
            $table->float('f_total_paid', 10, 2)->default(0.00);
            $table->enum('f_paid', ['0', '1'])->default('0');
            $table->unsignedInteger('f_paid_date')->nullable();
            $table->enum('f_active', ['0', '1'])->default('1');
            $table->enum('fp_recurring', ['0', '1'])->default('1');
            $table->enum('fp_main_monthly_fee', ['0', '1'])->default('1');
            $table->enum('fp_main_admission_fee', ['0', '1'])->default('0');
            $table->enum('f_reminder', ['0', '1', '2', '3', '4', '5'])->default('0');
            $table->enum('xxx_f_covid_hc_fee_concession', ['0', '1'])->default('0');
            $table->enum('xxx_f_covid_hc_late_fee', ['0', '1'])->default('0');
            $table->enum('xxx_f_covid_manual_concession_updated', ['0', '1'])->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_fees');
    }
};
