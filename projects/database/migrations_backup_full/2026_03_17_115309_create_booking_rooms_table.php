<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingRoomsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('booking_rooms')) {
            Schema::create('booking_rooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('room_id')->constrained();
                $table->dateTime('booking_start');
                $table->dateTime('booking_end');
                $table->foreignId('user_id')->constrained();
                $table->foreignId('status_id')->constrained('booking_statuses');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('booking_rooms');
    }
}