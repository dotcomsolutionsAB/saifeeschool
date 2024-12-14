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
        Schema::create('t_students', function (Blueprint $table) {
            $table->id();
            $table->integer('sch_id')->nullable();
            $table->text('st_roll_no')->nullable();
            // $table->text('st_password_hash');
            $table->integer('cg_id')->nullable();
            $table->string('st_first_name')->nullable();
            $table->string('st_last_name')->nullable();
            $table->enum('st_gender', ['M', 'F'])->nullable();
            $table->integer('st_dob')->nullable();
            $table->date('dob')->nullable();
            // $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Bombay', 'Rh-null'])->nullable();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Rare'])->nullable();
            $table->enum('st_bohra', ['0', '1'])->nullable();
            $table->string('st_its_id');
            $table->enum('st_house', ['red', 'blue', 'green', 'gold']);
            $table->float('st_wallet');
            $table->float('st_deposit');
            $table->text('st_gmail_address')->nullable();
            // $table->text('st_email')->nullable();
            $table->text('st_email_otp')->nullable();
            // $table->float('st_mobile_no')->nullable();
            $table->enum('st_external', ['0', '1']);
            $table->enum('st_on_roll', ['0', '1']);
            $table->string('st_year_of_admission');
            $table->string('st_admitted');
            $table->string('st_admitted_class');
            $table->string('flag');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_students');
    }
};
