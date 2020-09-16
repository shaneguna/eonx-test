<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMailChimpMembersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('mail_chimp_members', function(Blueprint $table)
		{
			$table->char('id', 36)->primary();
			$table->string('list_id');
			$table->string('mail_chimp_id')->nullable();
			$table->string('email_address');
			$table->string('status');
			$table->string('email_type')->nullable();
			$table->string('language')->nullable();
			$table->boolean('vip')->nullable();
			$table->longText('location')->nullable();
			$table->longText('marketing_permissions')->nullable();
			$table->string('ip_signup')->nullable();
			$table->string('timestamp_signup')->nullable();
			$table->string('ip_opt')->nullable();
			$table->string('timestamp_opt')->nullable();
			$table->longText('tags')->nullable();
			$table->string('email_id')->nullable();
			$table->string('unique_email_id')->nullable();
			$table->integer('member_rating')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('mail_chimp_members');
	}

}
