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
        Schema::create('t_purchase_item_products', function (Blueprint $table) {
            $table->id();
            $table->integer('purchase_id');
            $table->string('product', 255); // Product name
            $table->string('description', 255)->nullable(); // Product description
            $table->integer('quantity'); // Quantity of the product
            $table->string('unit', 50); // Unit (e.g., NOS, PCS, etc.)
            $table->float('price', 10, 2); // Price per unit
            $table->float('discount', 10, 2)->default(0); // Discount applied
            $table->string('hsn', 50)->nullable(); // HSN code
            $table->float('tax', 10, 2)->default(0); // Tax applied
            $table->float('cgst', 10, 2)->default(0); // CGST applied
            $table->float('sgst', 10, 2)->default(0); // SGST applied
            $table->float('igst', 10, 2)->default(0); // IGST applied
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_purchase_item_products');
    }
};
