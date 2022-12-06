<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotConnectionsTable extends Migration
{
    public function up()
    {
        Schema::create('bot_connections', function (Blueprint $table) {
            $table->id();
            $table->string('title',120);
            $table->string('username',120)->unique();
            $table->string('robot_token',50)->nullable()->comment('token to connect main server');
            $table->text('parameters')->nullable()->default(null);//telegrambot: offset:1,limit:5,...
            $table->string('webhook_token',50)->nullable()->default(null)->comment('token attached to webhook url');
            $table->boolean('active')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bot_connections');
    }
}
