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
        Schema::table('t_new_admission', function (Blueprint $table) {
            $table->unsignedBigInteger('child_photo_id')->nullable();
            $table->unsignedBigInteger('father_photo_id')->nullable();
            $table->unsignedBigInteger('mother_photo_id')->nullable();
            $table->unsignedBigInteger('birth_certificate_id')->nullable();
    
            // Foreign key relationships if needed (for example, if you want to reference the UploadModel)
            $table->foreign('child_photo_id')->references('id')->on('uploads')->onDelete('set null');
            $table->foreign('father_photo_id')->references('id')->on('uploads')->onDelete('set null');
            $table->foreign('mother_photo_id')->references('id')->on('uploads')->onDelete('set null');
            $table->foreign('birth_certificate_id')->references('id')->on('uploads')->onDelete('set null');
        });
    }
    
    public function down()
    {
        Schema::table('new_admissions', function (Blueprint $table) {
            $table->dropColumn('child_photo_id');
            $table->dropColumn('father_photo_id');
            $table->dropColumn('mother_photo_id');
            $table->dropColumn('birth_certificate_id');
        });
    }
};
