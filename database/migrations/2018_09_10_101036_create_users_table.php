<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('user_id');
                $table->unsignedInteger('company_id')->nullable();
                $table->string('first_name', 50)->nullable();
                $table->string('last_name', 50)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('password', 255)->nullable();
                $table->enum('user_type', ['driver', 'user', 'admin', 'company'])->default('user');
                $table->enum('gender', ['male', 'female', 'transgender', 'NA'])->default('NA');
                $table->string('blood_group', 10)->nullable();
                $table->string('mobile', 20)->unique()->nullable();
                $table->string('user_group', 10)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('state', 100)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('postcode', 15)->nullable();
                $table->string('profile_picture', 100)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('job_title', 50)->nullable();
                $table->string('employee_number', 50)->nullable();
                $table->date('employee_start_date')->nullable();
                $table->date('employee_end_date')->nullable();
                $table->string('driver_license_number', 30)->nullable();
                $table->string('driver_license_class', 30)->nullable();
                $table->string('driver_license_state', 30)->nullable();
                $table->date('license_expiry')->nullable();                
                $table->string('vehicle_model', 50)->nullable();
                $table->string('vehicle_picture', 100)->nullable();
                $table->string('miles', 20)->nullable();
                $table->string('vehicle_latitude', 20)->nullable();
                $table->string('vehicle_longitude', 20)->nullable();
                $table->string('device_id', 255)->nullable();
                $table->string('created_by', 20)->nullable();
                $table->string('updated_by', 20)->nullable();
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 255)->nullable();
                $table->smallInteger('is_deleted')->default(0);
                $table->string('short_token', 50)->nullable();
                $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
}