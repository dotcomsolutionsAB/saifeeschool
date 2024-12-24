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
        Schema::create('t_purchase_item_addons', function (Blueprint $table) {
            $table->id();
            $table->integer('purchase_id');
            $table->float('freight_value', 8, 2)->default(0);
            $table->string('freight_cgst', 10)->nullable();
            $table->string('freight_sgst', 10)->nullable();
            $table->float('freight_igst', 8, 2)->nullable();
            $table->float('roundoff', 8, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_purchase_item_addons');
    }
};
