<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTTermsTable extends Migration
{
    public function up()
    {
        Schema::create('t_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ay_id');  // Foreign Key for Academic Year
            $table->unsignedBigInteger('cg_id');  // Foreign Key for Class Group
            $table->integer('term');              // Term Number (e.g., 1, 2, etc.)
            $table->string('term_name', 255);     // Term Name (e.g., "Final Term")
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('ay_id')->references('id')->on('t_academic_years')->onDelete('cascade');
            $table->foreign('cg_id')->references('id')->on('t_class_groups')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_terms');
    }
}
