<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateLogTable extends Migration {

	public function up()
	{
		Schema::create('log', function(Blueprint $table) {
			$table->increments('id');
			$table->string('id_screen');
			$table->text('event_screen');
			$table->text('event');
			$table->string('event_type');
			$table->string('method');
			$table->string('fullurl');
			$table->string('ip');
			$table->string('mac');
			$table->string('agent');
			$table->unsignedBigInteger('user');
            $table->foreign('user')->references('id')->on('users')->onDelete('cascade');
			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down()
	{
		Schema::drop('log');
	}
}