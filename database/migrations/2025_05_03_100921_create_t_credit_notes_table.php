<?php

// database/migrations/xxxx_xx_xx_create_t_credit_notes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTCreditNotesTable extends Migration
{
    public function up()
    {
        Schema::create('t_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_no');
            $table->date('date');
            $table->decimal('amount', 12, 2);
            $table->string('collected_from');
            $table->string('cheque_no')->nullable();
            $table->text('description')->nullable();
            $table->string('log_user')->nullable();
            $table->timestamp('log_date')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_credit_notes');
    }
}