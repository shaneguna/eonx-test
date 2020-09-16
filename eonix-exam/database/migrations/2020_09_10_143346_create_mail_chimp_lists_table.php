<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMailChimpListsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mail_chimp_lists', function(Blueprint $table)
		{
			$table->char('id', 36)->primary();
			$table->string('name');
			$table->longText('contact');
			$table->string('permission_reminder');
			$table->longText('campaign_defaults');
			$table->boolean('email_type_option');
			$table->boolean('use_archive_bar')->nullable();
			$table->string('notify_on_subscribe')->nullable();
			$table->string('notify_on_unsubscribe')->nullable();
			$table->string('visibility')->nullable();
			$table->boolean('double_optin')->nullable();
			$table->boolean('marketing_permissions')->nullable();
			$table->string('mail_chimp_id')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('mail_chimp_lists');
	}

}
