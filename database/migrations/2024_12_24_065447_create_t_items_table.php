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
        Schema::create('t_items', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->longText('description');
            $table->string('category', 100)->default('');
            $table->string('sub_category', 100)->default('');
            $table->string('unit', 10);
            $table->string('price', 100);
            $table->string('discount', 100)->default('');
            $table->string('tax', 100);
            $table->string('hsn', 100);
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
        Schema::dropIfExists('t_items');
    }
};
