<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCompanyDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_details', function (Blueprint $table) {
            $table->increments('company_detail_id');
            $table->unsignedInteger('company_id')->nullable()->comment('company id is user_id of users table');
            $table->string('company_name', 255)->nullable();
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
        Schema::dropIfExists('company_details');
    }
}
