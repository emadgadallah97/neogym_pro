<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateSettingsTypeTable extends Migration {

	public function up()
	{
		Schema::create('settings_type', function(Blueprint $table) {
			$table->bigIncrements('id');
			$table->text('name');
			$table->text('description');
			$table->string('user_add');
			$table->string('account_id')->nullable();
			$table->timestamps();
			$table->softDeletes();
		});
	}

	public function down()
	{
		Schema::drop('settings_type');
	}
}