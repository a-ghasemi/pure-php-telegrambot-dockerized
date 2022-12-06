<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelegramRobotMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('telegram_robot_messages', function (Blueprint $table) {
            $table->id();

            $table->string('title',120)->nullable()->default(null);

            $table->integer('robot_id')->nullable()->default(null);
            $table->integer('user_id')->nullable()->default(null);
            $table->string('user_telegram_id',40)->nullable()->default(null);
            $table->boolean('session_id')->nullable()->default(null);
            $table->integer('returnof_id')->nullable()->default(null);//the message is auto return of telegram, for message #id in our robot_messages table

            $table->integer('mili')->default(0);
            $table->string('status')->nullable()->default(null);
            /*
                         received (from telegram),
                         NULL (received, but unkown robot or unwanted message),
                         bad_token,

                         pending,
                         sent,
                         delivered (by owner server, TELEGRAM HAS NO DELIVERY ON BOTS YET),

                         pending-delete,
            */


            $table->string('update_id',20)->nullable();


            /*      [Reply Types]
                        message,
                        edited_message,
                        channel_post,
                        edited_channel_post,
                        inline_query,
                        chosen_inline_result,
                        callback_query,
                        shipping_query,
                        pre_checkout_query
            */
            $table->string('reply_type',30)->nullable()->default(null);// https://core.telegram.org/bots/api#getting-updates + https://core.telegram.org/bots/api#available-types



            $table->string('reply_id')->nullable()->default(null);

            $table->integer('reply_from_id')->unsigned()->nullable()->default(null);
            $table->boolean('reply_from_isbot')->default(true);
            $table->string('reply_from_firstname')->nullable()->default(null);
            $table->string('reply_from_lastname')->nullable()->default(null);
            $table->string('reply_from_username')->nullable()->default(null);
            $table->string('reply_from_language_code')->nullable()->default(null);
            $table->integer('reply_status')->nullable()->default(null);

            $table->text('reply_othcontent')->nullable()->default(null);
            $table->text('reply_raw')->nullable()->default(null);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_robot_messages');
    }
}
