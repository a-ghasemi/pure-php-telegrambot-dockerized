<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLogsTable extends Migration
{
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->ipAddress('ip');
            $table->string('type',20)->nullable(); //error,warning,info,attack,quick,develop,postman
            $table->longText('message');
            $table->text('curr_url')->nullable()->default(NULL);
            $table->text('prev_url')->nullable()->default(NULL);
            $table->integer('mili')->default(false);

            $table->timestamp('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('logs');
    }
}
