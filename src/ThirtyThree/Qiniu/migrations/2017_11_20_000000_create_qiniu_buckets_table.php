<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQiniuBucketsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('file_qiniu_buckets', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->unique()->comment('用于标识唯一空间,upload_files表中 bucket 对应的值,方便数据迁移');
            $table->string('name');
            $table->string('bucket');
            $table->string('domain');
            $table->string('visibility');
            $table->string('access_key');
            $table->string('secret_key');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('file_qiniu_buckets');
    }
}
