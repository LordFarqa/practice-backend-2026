<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->integer('number');
                $table->foreignId('hotel_id')->constrained();
                $table->foreignId('class_id')->constrained('room_classes');
                $table->integer('floor');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('rooms');
    }
}