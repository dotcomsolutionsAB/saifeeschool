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
        Schema::create('t_txn_types', function (Blueprint $table) {
            $table->id();
            $table->enum('txn_type_from', ['wallet', 'student', 'fee', 'late_fee', 'deposit', 'school'])
            ->default('student'); // Source of the transaction
            $table->enum('txn_type_to', ['wallet', 'student', 'fee', 'late_fee', 'deposit', 'school'])
                    ->default('wallet'); // Destination of the transaction
            $table->text('txn_type_name')->nullable(); // Short description
            $table->text('txn_type_description')->nullable(); // Detailed description
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_txn_types');
    }
};
