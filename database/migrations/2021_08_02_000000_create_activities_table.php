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
            $table->string('log_name');
            $table->text('description')->nullable();
            $table->string('ref_model')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('causer_model')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->text('properties')->default('[]');
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
