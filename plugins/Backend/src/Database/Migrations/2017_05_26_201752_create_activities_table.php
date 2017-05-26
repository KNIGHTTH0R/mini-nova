<?php

use Mini\Database\Schema\Blueprint;
use Mini\Database\Migrations\Migration;


class CreateActivitiesTable extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('activities', function ($table) {
			$table->increments('id');
			$table->string('session', 100);
			$table->integer('user_id')->unsigned()->nullable();
			$table->string('ip', 40)->nullable();
			$table->integer('last_activity')->unsigned();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('activities');
	}
}
