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
        Schema::create('t_teachers', function (Blueprint $table) {
            $table->id();
            $table->integer('teacher_id');
            $table->string('name');
            $table->text('address')->nullable();
            $table->enum('gender', ['M', 'F'])->nullable();
            $table->date('dob')->nullable();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Rare'])->nullable();
            $table->enum('is_class_teacher', ['0', '1']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_teachers');
    }
};
