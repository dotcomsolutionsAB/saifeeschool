<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('t_subjectFM', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ay_id'); // Academic Year ID
            $table->unsignedBigInteger('cg_id'); // Class Group ID
            $table->unsignedBigInteger('subject_id'); // Subject ID
            $table->integer('full_marks'); // Full Marks for the subject
            $table->integer('pass_marks')->nullable(); // Pass Marks (nullable)
            $table->timestamps();

            // Foreign keys
            $table->foreign('ay_id')->references('id')->on('t_academic_years')->onDelete('cascade');
            $table->foreign('cg_id')->references('id')->on('t_class_groups')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('t_subjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_subjectFM');
    }
};