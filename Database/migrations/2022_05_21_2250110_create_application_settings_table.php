<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateApplicationSettingsTable extends Migration {

	public function up()
	{
		Schema::create('application_settings', function(Blueprint $table) {
			$table->increments('id');
			$table->text('name');
			$table->unsignedBigInteger('id_settings_type');
            $table->foreign('id_settings_type')->references('id')->on('settings_type')->onDelete('cascade');
			$table->string('user_add');
			$table->string('account_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down()
	{
		Schema::drop('application_settings');
	}
}