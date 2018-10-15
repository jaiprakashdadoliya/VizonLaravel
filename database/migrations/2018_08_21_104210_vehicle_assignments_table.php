<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class VehicleAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle_assignments', function (Blueprint $table){
            $table->increments('vehicle_assignment_id');
            $table->unsignedInteger('vehicle_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->text('description')->nullable();
            $table->date('start_time')->nullable();
            $table->date('end_time')->nullable();
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
        Schema::dropIfExists('vehicle_assignments');
    }
}
