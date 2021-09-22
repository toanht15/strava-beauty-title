<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->integer('athlete_id')->nullable(false);
            $table->bigInteger('activity_id')->nullable(false)->unique();
            $table->string('name')->nullable(true);
            $table->float('distance')->nullable(true);
            $table->integer('moving_time')->nullable(true);
            $table->integer('elapsed_time')->nullable(true);
            $table->integer('total_elevation_gain')->nullable(true);
            $table->string('type')->nullable(true);
            $table->integer('workout_type')->nullable(true);
            $table->string('start_date', 0)->nullable();
            $table->string('start_date_local', 0)->nullable();
            $table->string('timezone')->nullable(true);
            $table->float('start_latlng')->nullable(true);
            $table->float('end_latlng')->nullable(true);
            $table->string('location_city')->nullable(true);
            $table->string('location_state')->nullable(true);
            $table->string('location_country')->nullable(true);
            $table->integer('achievement_count')->nullable(true);
            $table->integer('kudos_count')->nullable(true);
            $table->integer('comment_count')->nullable(true);
            $table->integer('athlete_count')->nullable(true);
            $table->boolean('manual')->nullable(true);
            $table->boolean('private')->nullable(true);
            $table->float('average_speed')->nullable(true);
            $table->float('max_speed')->nullable(true);
            $table->float('average_cadence')->nullable(true);
            $table->boolean('has_heartrate')->nullable(true);
            $table->float('average_heartrate')->nullable(true);
            $table->float('max_heartrate')->nullable(true);
            $table->integer('pr_count')->nullable(true);
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
        Schema::dropIfExists('activities');
    }
}
