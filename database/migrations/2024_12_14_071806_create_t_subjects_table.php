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
        Schema::create('t_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 256)->default(''); // Subject name
            // $table->string('SubInit', 10)->default(''); // Subject initials
            $table->integer('cg_group')->default(0); // Class group
            $table->string('type', 10)->default(''); // Subject type
            $table->string('marks', 100)->default(''); // Marks
            $table->string('prac', 100)->default(''); // Practical marks
            $table->string('serial', 100)->default(''); // Serial number
            $table->string('category', 100)->default(''); // Category
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_subjects');
    }
};
