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
        Schema::create('t_txns', function (Blueprint $table) {
            $table->id();
            $table->integer('st_id')->nullable(); // Student ID (foreign key reference, optional)
            $table->integer('sch_id')->nullable(); // School ID (foreign key reference, optional)
            $table->integer('txn_type_id'); // Transaction type ID (required)
            $table->integer('txn_date')->nullable(); // Transaction date (stored as integer, optional)
            $table->enum('txn_mode', [
                'internal', 'cash', 'cheque', 'draft', 'pg', 'imps', 'neft', 'rtgs', 'paytm', 'pos'
            ])->default('internal'); // Transaction mode with a default value of 'internal'
            $table->float('txn_amount', 10, 2)->default(0.00); // Transaction amount with default value of 0.00
            $table->integer('f_id')->nullable(); // Fee ID (optional)
            $table->enum('f_normal', ['1', '0'])->default('0'); // Flag for normal fee transactions
            $table->enum('f_late', ['1', '0'])->default('0'); // Flag for late fee transactions
            $table->integer('txn_tagged_to_id')->nullable(); // Tagged transaction ID (if this transaction reverses another)
            $table->text('txn_reason')->nullable(); // Reason for the transaction (optional)
            $table->date('date')->nullable(); // Transaction date as a `date` field (optional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_txns');
    }
};
