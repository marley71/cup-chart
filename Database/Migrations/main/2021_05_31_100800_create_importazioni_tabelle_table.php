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
        Schema::create('importazioni_tabelle', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('importazione_id')->unsigned()->index()->nullable()->default(null);// varchar(4) DEFAULT NULL,
            $table->foreign('importazione_id')->references('id')->on('importazioni')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->string('nome',2000)->nullable();
            $table->text('descrizione')->nullable();
            $table->string('sheetname')->nullable();
            $table->integer('progressivo')->nullable()->default(1);
            $table->string('elastic_id')->nullable();
            $table->longText('metadata')->nullable();
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
        Schema::drop('importazioni_tabelle');
    }

};
