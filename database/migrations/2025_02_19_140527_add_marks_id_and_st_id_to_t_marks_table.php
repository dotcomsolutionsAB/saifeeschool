<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('t_marks', function (Blueprint $table) {
            $table->string('marks_id')->after('id')->nullable()->unique();
            $table->unsignedBigInteger('st_id')->after('ay_id')->nullable();
        });
    }
    
    public function down()
    {
        Schema::table('t_marks', function (Blueprint $table) {
            $table->dropColumn(['marks_id', 'st_id']);
        });
    }
};
