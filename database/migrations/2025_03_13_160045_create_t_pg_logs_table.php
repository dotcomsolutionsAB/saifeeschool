<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('t_pg_logs', function (Blueprint $table) {
            $table->id(); // Auto-incremental primary key
            $table->string('pg_reference_no')->unique(); // Unique reference number
            $table->unsignedBigInteger('st_id'); // Student ID
            $table->string('remarks')->nullable(); // Remarks about the payment
            $table->string('f_id'); // Fee IDs (comma-separated)
            $table->decimal('amount', 10, 2); // Payment amount
            $table->string('status')->default('pending'); // Payment status (pending/success/failed)
            $table->timestamp('created_at')->useCurrent(); // Auto-set timestamp
        });
    }

    public function down()
    {
        Schema::dropIfExists('pg_logs');
    }
};