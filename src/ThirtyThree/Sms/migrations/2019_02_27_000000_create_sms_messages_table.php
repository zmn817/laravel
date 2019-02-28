<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSmsMessagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('country_code')->nullable()->comment('手机号国家代码');
            $table->string('phone_number')->nullable()->comment('手机号');
            $table->string('content')->nullable();
            $table->string('template')->nullable();
            $table->text('data')->nullable();
            $table->boolean('success')->nullable();
            $table->text('errors')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('sms_messages');
    }
}
