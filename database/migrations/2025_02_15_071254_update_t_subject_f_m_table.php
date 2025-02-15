<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('t_subjectFM', function (Blueprint $table) {
            // Check and add missing columns
            // if (!Schema::hasColumn('t_subjectFM', 'subj_id')) {
            //     $table->unsignedBigInteger('subj_id')->after('id');
            // }
            // if (!Schema::hasColumn('t_subjectFM', 'subj_name')) {
            //     $table->string('subj_name')->after('subj_id');
            //csv
            }
            if (!Schema::hasColumn('t_subjectFM', 'subj_init')) {
                $table->string('subj_init')->after('subj_name');
            }
            if (!Schema::hasColumn('t_subjectFM', 'cg_id')) {
                $table->unsignedBigInteger('cg_id')->after('subj_init');
            }
            if (!Schema::hasColumn('t_subjectFM', 'term_id')) {
                $table->unsignedBigInteger('term_id')->after('cg_id');
            }
            if (!Schema::hasColumn('t_subjectFM', 'type')) {
                $table->string('type', 2)->after('term_id')->default('M'); // Default 'M' for main subject
            }
            if (!Schema::hasColumn('t_subjectFM', 'theory')) {
                $table->integer('theory')->nullable()->after('type');
            }
            if (!Schema::hasColumn('t_subjectFM', 'oral')) {
                $table->integer('oral')->nullable()->after('theory');
            }
            if (!Schema::hasColumn('t_subjectFM', 'prac')) {
                $table->integer('prac')->nullable()->after('oral');
            }
            if (!Schema::hasColumn('t_subjectFM', 'marks')) {
                $table->integer('marks')->after('prac');
            }

            // Add Foreign Keys (if needed)
            $table->foreign('subj_id')->references('id')->on('t_subjects')->onDelete('cascade');
            $table->foreign('cg_id')->references('id')->on('t_class_groups')->onDelete('cascade');
            $table->foreign('term_id')->references('id')->on('t_terms')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('t_subjectFM', function (Blueprint $table) {
            $table->dropColumn(['subj_id', 'subj_name', 'subj_init', 'cg_id', 'term_id', 'type', 'theory', 'oral', 'prac', 'marks']);
        });
    }
};