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
        Schema::create('t_teacher_application', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name
            $table->enum('gender', ['M', 'F'])->comment('M: Male, F: Female'); // Gender
            $table->date('dob')->nullable()->comment('Date of Birth'); // Date of birth
            $table->string('contact_number', 15)->unique()->nullable(); // Contact number
            $table->string('email', 255)->unique()->nullable(); // Email address
            // $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Rare'])->nullable(); // Blood group
            $table->string('apply_for', 100)->nullable(); // Designation
            $table->string('qualification', 255)->nullable(); // Educational qualifications
            $table->integer('experience_years')->default(0)->comment('Years of experience'); // Experience
            // $table->enum('teacher_type', ['Permanent', 'Temporary', 'Contract'])->default('Permanent'); // Employment type
            // $table->date('joining_date')->comment('Date of joining'); // Joining date
            // $table->date('leaving_date')->nullable()->comment('Date of leaving'); // Leaving date
            $table->string('address_line1', 255)->nullable(); // Residential address line 1
            $table->string('address_line2', 255)->nullable(); // Residential address line 2
            $table->string('city', 100)->nullable(); // City
            $table->string('state', 100)->nullable(); // State
            $table->string('country', 100)->default('India'); // Country
            $table->string('pincode', 10)->nullable(); // PIN code
            // $table->string('emergency_contact_name', 100)->nullable(); // Emergency contact person
            // $table->string('emergency_contact_relation', 50)->nullable(); // Relation to the teacher
            // $table->string('emergency_contact_number', 15)->nullable(); // Emergency contact number
            $table->unsignedBigInteger('resume_path')->nullable(); // Resume/CV file path
            $table->unsignedBigInteger('photo_path')->nullable(); // Photograph file path
            $table->unsignedBigInteger('id_proof_path')->nullable(); // ID proof file path
            $table->unsignedBigInteger('qualification_docs_path')->nullable(); // Qualification documents file path
            $table->enum('status', ['Active', 'Inactive'])->default('Active'); // Status
            $table->text('remarks')->nullable(); // Additional remarks
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_teacher_application');
    }
};
