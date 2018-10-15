<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('trips')) {
            Schema::create('trips', function (Blueprint $table) {
                $table->increments('trip_id');
                $table->integer('user_id')->comment('Foreign key for users table')->nullable();
                $table->integer('company_id')->comment('Company id')->nullable();
                $table->integer('vehicle_id')->comment('Foreign key for vehicle table')->nullable();
                $table->timestamp('start_time')->nullable();
                $table->timestamp('end_time')->nullable();
                $table->string('start_address', 255)->nullable();
                $table->string('end_address', 255)->nullable();
                $table->decimal('start_latitude', 10, 8)->nullable();
                $table->decimal('start_longitude', 11, 8)->nullable();
                $table->decimal('end_latitude', 10, 8)->nullable();
                $table->decimal('end_longitude', 11, 8)->nullable();
                $table->decimal('distance', 4, 2)->nullable();
                $table->integer('created_by');
                $table->integer('updated_by');
                $table->enum('resource_type', ['ios', 'android', 'web'])->default('web');
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 255)->nullable();
                $table->smallInteger('is_deleted')->comment('Soft Delete. Default value 0')->default(0);
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
        Schema::dropIfExists('trips');
    }
}
