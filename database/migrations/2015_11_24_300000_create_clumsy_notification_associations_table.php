<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClumsyNotificationAssociationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clumsy_notification_associations', function (Blueprint $table) {

            $table->increments('id');
            $table->integer('notification_id')->unsigned()->index();
            $table->string('association_type')->index();
            $table->integer('association_id')->unsigned()->index();
            $table->boolean('triggered')->default(0);
            $table->boolean('read')->default(0);

            $table->foreign('notification_id')->references('id')->on('clumsy_notifications')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clumsy_notification_associations', function ($table) {
            $table->dropForeign('clumsy_notification_associations_notification_id_foreign');
        });

        Schema::drop('clumsy_notification_associations');
    }
}
