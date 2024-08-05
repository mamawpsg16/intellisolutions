<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
         Schema::create('test_users', function (Blueprint $table) {
             $table->id();
             $table->string('name');
             $table->integer('service_provider_id');
            //  $table->jsonb('roles')->nullable(); // FOR JSON
             $table->mediumText('roles')->nullable(); //FOR TEXT
             $table->timestamp('last_login_time')->nullable();
         });
     }
 
     public function down()
     {
         Schema::dropIfExists('test_users');
     }
};
