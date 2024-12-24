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
        Schema::create('t_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('company', 100);
            $table->string('name', 110);
            $table->string('address', 256);
            $table->string('state', 100);
            $table->string('country', 100);
            $table->string('mobile', 100);
            $table->string('email', 100);
            $table->longText('documents')->nullable();
            $table->longText('bank_details');
            $table->longText('notes')->nullable();
            $table->string('gstin', 15);
            $table->string('gstin_type', 100);
            $table->string('notification', 256)->nullable();
            $table->string('log_user', 100);
            $table->date('log_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_suppliers');
    }
};
