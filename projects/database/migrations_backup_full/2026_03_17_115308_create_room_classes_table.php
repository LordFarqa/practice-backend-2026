<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomClassesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('room_classes')) {
            Schema::create('room_classes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->float('price_per_day');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('room_classes');
    }
}