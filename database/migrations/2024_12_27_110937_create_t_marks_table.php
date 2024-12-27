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
        Schema::create('t_marks', function (Blueprint $table) {
            $table->id();
            $table->integer('session')->nullable(); // Academic Session (nullable)
            $table->string('st_roll_no', 100)->default(''); // Student Roll Number
            $table->integer('subj_id')->nullable(); // Subject ID (nullable)
            $table->integer('cg_id')->nullable(); // Class Group ID (nullable)
            $table->integer('term')->nullable(); // Term (nullable)
            $table->integer('unit')->nullable(); // Unit (nullable)
            $table->string('marks', 100)->default(''); // Marks
            $table->string('prac', 100)->default(''); // Practical Marks
            $table->integer('serialNo')->nullable(); // Serial Number (nullable)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_marks');
    }
};
