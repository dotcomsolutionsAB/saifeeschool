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
        Schema::create('t_pg_responses', function (Blueprint $table) {
            $table->id();
            $table->string('response_code', 100)->default('');
            $table->bigInteger('unique_ref_number');
            $table->date('transaction_date')->nullable();
            $table->time('transaction_time')->nullable();
            $table->float('total_amount');
            $table->string('interchange_value', 256)->nullable(); // Updated to varchar
            $table->string('tdr', 256)->nullable(); // Updated to varchar
            $table->string('payment_mode', 256)->default('');
            $table->bigInteger('submerchant_id');
            $table->bigInteger('reference_no');
            $table->bigInteger('icid');
            $table->longText('rs')->default('');
            $table->enum('tps', ['Y', 'N'])->nullable();
            $table->string('mandatory_fields', 256)->default('');
            $table->string('optional_fields', 256)->nullable();
            $table->longText('rsv')->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_pg_responses');
    }
};
