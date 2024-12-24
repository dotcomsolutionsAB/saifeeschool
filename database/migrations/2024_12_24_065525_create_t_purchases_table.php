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
        Schema::create('t_purchases', function (Blueprint $table) {
            $table->id();
            $table->string('supplier', 1000);
            $table->string('purchase_invoice_no', 100);
            $table->date('purchase_invoice_date');
            $table->string('series', 100);
            // $table->longText('items');
            // $table->longText('addons');
            $table->string('currency', 100)->default('');
            $table->float('total');
            $table->float('paid')->default('0');
            // $table->longText('tax');
            $table->float('cgst');
            $table->float('sgst');
            $table->float('igst');
            $table->integer('status')->default('0');
            $table->string('log_user', 1000);
            $table->date('log_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_purchases');
    }
};
