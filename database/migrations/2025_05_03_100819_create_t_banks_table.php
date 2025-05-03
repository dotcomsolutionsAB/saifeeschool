<?php

// database/migrations/xxxx_xx_xx_create_t_banks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTBanksTable extends Migration
{
    public function up()
    {
        Schema::create('t_banks', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['Deposit', 'Withdrawal']);
            $table->decimal('amount', 12, 2);
            $table->text('comments')->nullable();
            $table->date('date');
            $table->string('log_user')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('t_banks');
    }
}
