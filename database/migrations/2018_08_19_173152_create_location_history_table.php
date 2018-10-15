<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('location_history')) {
            Schema::create('location_history', function (Blueprint $table) {
                $table->increments('history_id');
                $table->integer('user_id')->comment('Foreign key for user table')->nullable();
                $table->decimal('user_lattitude', 10, 8)->nullable();
                $table->decimal('user_longitude', 11, 8)->nullable();
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('ios');
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location_history');
    }
}
