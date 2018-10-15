<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserVideosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_videos', function (Blueprint $table) {
            $table->increments('video_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedInteger('vehicle_id')->nullable();
            $table->string('bucket_name', 255)->nullable();
            $table->string('video_name', 255)->nullable();
            $table->text('video_thumbnail_url')->nullable();
            $table->string('video_url', 255)->nullable();
            $table->string('video_size', 255)->nullable();
            $table->smallInteger('is_uploaded_to_server')->default(0)->commnet('0 = default, 1 = uploaded');
            $table->smallInteger('is_downloaded_from_server')->default(0)->commnet('0 = default, 1 = downloaded');
            $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
            $table->string('user_agent', 255)->nullable();
            $table->string('ip_address', 255)->nullable();
            $table->smallInteger('is_deleted')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_videos');
    }
}
