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
        Schema::create('t_student_details', function (Blueprint $table) {
            $table->id();
            $table->integer('st_id');
            $table->integer('aadhaar_no');
            $table->text('residential_address1');
            $table->text('residential_address2')->nullable();
            $table->text('residential_address3')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->integer('pincode');
            $table->integer('class_group');
            $table->string('f_name');
            $table->string('f_email');
            $table->string('f_contact');
            $table->enum('f_occupation', ['employed', 'self-employed', 'none']);
            $table->string('f_business_name')->nullable();
            $table->string('f_business_nature')->nullable();
            $table->string('f_business_address1')->nullable();
            $table->string('f_business_address2')->nullable();
            $table->string('f_business_city')->nullable();
            $table->string('f_business_state')->nullable();
            $table->string('f_business_country')->nullable();
            $table->string('f_business_pincode')->nullable();
            $table->string('f_employer_name')->nullable();
            $table->string('f_designation')->nullable();
            $table->string('f_work_address1')->nullable();
            $table->string('f_work_address2')->nullable();
            $table->string('f_work_city')->nullable();
            $table->string('f_work_state')->nullable();
            $table->string('f_work_country')->nullable();
            $table->string('f_work_pincode')->nullable();
            $table->string('m_name');
            $table->string('m_contact');
            $table->enum('m_occupation', ['employed', 'self-employed', 'home-maker']);
            $table->string('m_business_name')->nullable();
            $table->string('m_business_nature')->nullable();
            $table->string('m_business_address1')->nullable();
            $table->string('m_business_address2')->nullable();
            $table->string('m_business_city')->nullable();
            $table->string('m_business_state')->nullable();
            $table->string('m_business_country')->nullable();
            $table->string('m_business_pincode')->nullable();
            $table->string('m_employer_name')->nullable();
            $table->string('m_designation')->nullable();
            $table->string('m_work_address1')->nullable();
            $table->string('m_work_address2')->nullable();
            $table->string('m_work_city')->nullable();
            $table->string('m_work_state')->nullable();
            $table->string('m_work_country')->nullable();
            $table->string('m_work_pincode')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_student_details');
    }
};
