<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoragesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('storages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('driver');
            $table->string('bucket');
            $table->timestamps();
        });

        Schema::create('storage_scenes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('storage_id');
            $table->string('platform');
            $table->string('scene');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('storage_id')->references('id')->on('storages')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('storage_scenes');
        Schema::dropIfExists('storages');
    }
}
