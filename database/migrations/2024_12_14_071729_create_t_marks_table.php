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
            $table->string('session', 100)->default(''); // Session
            $table->string('st_roll_no', 100)->default(''); // Student roll number
            $table->string('subj_id', 100)->default(''); // Subject ID
            $table->string('cg_id', 100)->default(''); // Class group ID
            $table->string('term', 100)->default(''); // Term
            $table->string('unit', 100)->default(''); // Unit
            $table->string('marks', 100)->default(''); // Marks
            $table->string('prac', 100)->default(''); // Practical marks
            $table->string('serialNo', 100)->default(''); // Serial number
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
