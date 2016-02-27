<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClumsyNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clumsy_notifications', function (Blueprint $table) {

            $table->increments('id');
            $table->integer('activity_id')->unsigned()->index();
            $table->string('slug')->nullable()->default(null)->index();
            $table->datetime('visible_from');

            $table->foreign('activity_id')->references('id')->on('clumsy_activities')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('clumsy_notifications');
    }
}
