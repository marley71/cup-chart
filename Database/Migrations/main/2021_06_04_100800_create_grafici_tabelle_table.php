<?php

use Illuminate\Database\Migrations\Migration;
use Gecche\Breeze\Database\Schema\Blueprint;

return new class  extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grafici_tabelle', function (Blueprint $table) {
            $table->increments('id');
            $table->text('nome')->nullable();
            $table->text('html');
            $table->text('attributes');
            $table->integer('importazione_tabelle_id')->unsigned()->index()->nullable()->default(null);// varchar(4) DEFAULT NULL,
            $table->foreign('importazione_tabelle_id')->references('id')->on('importazioni_tabelle')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->nullableTimestamps();
            $table->nullableOwnerships();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('grafici_tabelle');
    }

};
