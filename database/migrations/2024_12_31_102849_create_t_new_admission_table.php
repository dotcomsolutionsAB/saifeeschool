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
        Schema::create('t_new_admission', function (Blueprint $table) {
            $table->id();
            $table->string('application_no', 100)->unique(); // Unique application number
            $table->integer('ay_id'); // Academic year ID
            $table->string('class', 100); // Class
            $table->date('date'); // Date of application
            $table->string('first_name', 256)->default(''); // First name
            $table->string('last_name', 100)->default(''); // Last name
            $table->enum('gender', ['m', 'f'])->comment('m: Male, f: Female'); // Gender
            $table->date('date_of_birth'); // Date of birth
            $table->string('last_school', 1000)->default(''); // Last school attended
            $table->string('last_school_address', 1000)->default(''); // Address of the last school
            $table->string('aadhaar_no')->default(0); // Aadhaar number
            // $table->text('father_details')->default(''); // Father's details

            // Father's details
            $table->string('father_first_name', 256)->nullable(); // Father's first name
            $table->string('father_last_name', 256)->nullable(); // Father's last name
            $table->string('father_name', 512)->nullable(); // Father's full name
            $table->string('father_education', 512)->nullable(); // Father's education
            $table->enum('father_occupation', ['employed', 'business', 'no-occupation'])->nullable(); // Father's occupation
            $table->string('father_employer', 256)->nullable(); // Father's employer (if employed)
            $table->string('father_designation', 256)->nullable(); // Father's designation (if employed)
            $table->text('father_business')->nullable(); // Father's business name (if business)
            $table->text('father_business_nature')->nullable(); // Nature of the business (if business)
            $table->text('father_monthly_income')->nullable(); // Father's monthly income
            $table->string('father_mobile', 25)->nullable(); // Father's mobile
            $table->string('father_email', 256)->nullable(); // Father's email
            $table->string('father_work_business_address', 1000)->nullable();

            // $table->text('mother_details')->default(''); // Mother's details

            // Mother's details
            $table->string('mother_first_name', 256)->nullable(); // Mother's first name
            $table->string('mother_last_name', 256)->nullable(); // Mother's last name
            $table->string('mother_name', 512)->nullable(); // Mother's full name
            $table->string('mother_education', 512)->nullable(); // Mother's education
            $table->enum('mother_occupation', ['employed', 'business', 'home-maker', 'not-applicable'])->nullable(); // Mother's occupation
            $table->string('mother_employer', 256)->nullable(); // Mother's employer (if employed)
            $table->string('mother_designation', 256)->nullable(); // Mother's designation (if employed)
            $table->text('mother_business')->nullable(); // Mother's business name (if business)
            $table->text('mother_business_nature')->nullable(); // Nature of the business (if business)
            $table->text('mother_monthly_income')->nullable(); // Mother's monthly income
            $table->string('mother_mobile', 25)->nullable(); // Mother's mobile
            $table->string('mother_email', 256)->nullable(); // Mother's email
            $table->string('mother_work_business_address', 1000)->nullable();

            $table->string('siblings_name1')->nullable(); // Sibling information
            $table->string('siblings_class1')->nullable(); // Sibling information
            $table->string('siblings_roll_no1')->nullable(); // Sibling information

            $table->string('siblings_name2')->nullable(); // Sibling information
            $table->string('siblings_class2')->nullable(); // Sibling information
            $table->string('siblings_roll_no2')->nullable(); // Sibling information

            $table->string('siblings_name3')->nullable(); // Sibling information
            $table->string('siblings_class3')->nullable(); // Sibling information
            $table->string('siblings_roll_no3')->nullable(); // Sibling information
            // $table->longText('address')->default(''); // Residential address

            // address
            $table->string('address_1', 1000)->nullable(); // Address line 1
            $table->string('address_2', 1000)->nullable(); // Address line 2
            $table->string('city', 256)->nullable(); // City
            $table->string('state', 256)->nullable(); // State
            $table->string('country', 256)->nullable(); // Country
            $table->string('pincode', 10)->nullable(); // Pincode

            // $table->longText('other_info')->default(''); // Other information
            $table->longText('attracted')->nullable(); // attracted
            $table->longText('strengths')->nullable(); // strengths
            $table->longText('remarks')->nullable(); // remarks

            $table->enum('ad_paid', ['0', '1'])->default('0')->comment('0: Not paid, 1: Paid'); // Admission fee paid
            $table->string('transaction_id', 100)->default(''); // Transaction ID
            $table->string('transaction_date', 100)->nullable(); // Transaction date
            $table->date('interview_date')->nullable(); // Interview date
            $table->enum('interview_status', ['0', '1'])->default('0')->comment('0: Not cleared, 1: Cleared'); // Interview status
            $table->enum('added_to_school', ['0', '1'])->default('0')->comment('0: Not added, 1: Added'); // Added to school
            $table->longText('comments')->default(''); // Comments
            $table->enum('printed', ['0', '1'])->default('0')->comment('0: Not printed, 1: Printed'); // Print status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_new_admission');
    }
};
