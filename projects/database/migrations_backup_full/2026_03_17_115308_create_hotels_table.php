<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotelsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hotels')) {
            Schema::create('hotels', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->json('address');
                $table->json('class');
                $table->float('average_rating')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('hotels');
    }
}