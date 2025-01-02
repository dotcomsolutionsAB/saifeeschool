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
        Schema::create('t_character_certificate', function (Blueprint $table) {
            $table->id();
            $table->date('dated')->nullable(false); // Date of the record
            $table->integer('serial_no')->nullable(false); // Serial number
            $table->string('registration_no', 100)->default(''); // Registration number
            $table->integer('st_id')->default(0); // Student ID
            $table->string('st_roll_no', 100)->default(''); // Student roll number
            $table->string('name', 512)->default(''); // Student name
            $table->date('joining_date')->nullable(); // Joining date
            $table->date('leaving_date')->nullable(); // Leaving date
            $table->string('stream', 100)->default(''); // Stream
            $table->string('date_from', 100)->default(''); // Date from
            $table->date('dob')->nullable(false); // Date of birth
            $table->string('dob_words', 256)->default(''); // Date of birth in words
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_character_certificate');
    }
};
