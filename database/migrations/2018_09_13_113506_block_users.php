<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BlockUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('block_users', function (Blueprint $table) {
            $table->increments('block_user_id');
            $table->unsignedInteger('user_id')->nullable()->comment('Blocker');
            $table->unsignedInteger('user_to_be_blocked')->nullable()->comment('Blocked');            
            $table->smallInteger('is_blocked')->default(0)->comment('0=active, 1=block');
            $table->string('reason', 20)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->ipAddress('ip_address')->nullable();
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
        Schema::dropIfExists('block_users');
    }
}
