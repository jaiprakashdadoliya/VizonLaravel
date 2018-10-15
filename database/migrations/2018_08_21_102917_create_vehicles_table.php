<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->increments('vehicle_id');
            $table->string('vehicle_name', 20)->nullable();
            $table->string('insurance_number', 50)->nullable();
            $table->string('registration_number', 20)->nullable();
            $table->enum('vehicle_type', ['bus','mini-bus','van'])->default('bus');
            $table->string('vehicle_year', 10)->nullable();
            $table->string('vehicle_make', 50)->nullable();
            $table->string('vehicle_model', 50)->nullable();
            $table->string('vehicle_trim', 50)->nullable();
            $table->string('vehicle_sate', 50)->nullable();
            $table->string('vehicle_primary_meter', 20)->nullable();
            $table->double('vehicle_fuel_unit', 10,8)->nullable();
            $table->string('vehicle_avatar', 100)->nullable();
            $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
            $table->string('user_agent', 255)->nullable();
            $table->ipAddress('ip_address')->nullable();
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
        Schema::dropIfExists('vehicles');
    }
}
