<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClumsyActivityMetaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clumsy_activity_meta', function (Blueprint $table) {

            $table->increments('id');
            $table->integer('activity_id')->unsigned()->index();
            $table->string('key')->nullable()->default(null)->index();
            $table->text('value')->nullable()->default(null);

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
        Schema::table('clumsy_activity_meta', function ($table) {
            $table->dropForeign('clumsy_activity_meta_activity_id_foreign');
        });

        Schema::drop('clumsy_activity_meta');
    }
}
