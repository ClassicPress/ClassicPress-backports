<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWPCommitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('w_p_commits', function (Blueprint $table) {
            $table->increments('id');
            $table->string('sha');
            $table->string('nodeid');
            $table->longtext('message');
            $table->string('commit_date');
            $table->string('html_link');
            $table->integer('status');
            $table->longtext('decline_response')->nullable();
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
        Schema::dropIfExists('w_p_commits');
    }
}
