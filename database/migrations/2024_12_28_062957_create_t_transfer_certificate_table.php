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
        Schema::create('t_transfer_certificate', function (Blueprint $table) {
            $table->id();
            $table->date('dated')->nullable(false); // Date of the record
            $table->integer('serial_no'); // Serial number
            $table->string('registration_no', 100)->default(''); // Registration number
            $table->integer('st_id')->default(0); // Student ID
            $table->string('st_roll_no')->default(0); // Student roll number
            $table->string('name', 512)->default(''); // Student name
            $table->string('father_name', 256)->default(''); // Father's name
            $table->string('joining_class', 100)->nullable(); // Joining class
            $table->date('joining_date')->nullable(); // Joining date
            $table->date('leaving_date')->nullable(); // Leaving date
            $table->string('prev_school', 256)->default(''); // Previous school
            $table->string('character', 100)->default(''); // Character description
            $table->string('class', 100)->default(''); // Current class
            $table->string('stream', 100)->nullable(); // Stream
            $table->string('date_from', 100)->nullable(); // Date from
            $table->string('date_to', 100)->nullable(); // Date to
            $table->date('dob')->nullable(); // Date of birth
            $table->string('dob_words', 256)->default(''); // Date of birth in words
            $table->enum('promotion', ['0', '1'])->default('0'); // Promotion status
            $table->enum('status', ['Not Applicable', 'Refused', 'Promoted'])->default('Not Applicable'); // Status            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_transfer_certificate');
    }
};
