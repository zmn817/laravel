<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQiniuFopTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('file_qiniu_fop', function (Blueprint $table) {
            $table->increments('id');
            $table->string('fop_id')->index();
            $table->string('bucket_name')->nullable()->comment('配置文件中的 bucket 名称');
            $table->string('bucket')->nullable()->comment('真实的 bucket 名称');
            $table->string('key', 2000)->nullable()->comment('文件路径');
            $table->string('usage')->nullable()->comment('用途,用于处理回调');
            $table->text('save_info')->nullable()->comment('保存的文件信息');
            $table->text('context')->nullable()->comment('上下文');
            $table->string('pipeline')->nullable();
            $table->integer('code')->nullable();
            $table->string('desc')->nullable();
            $table->string('request_id')->nullable();

            $table->longText('items')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('file_qiniu_fop');
    }
}
