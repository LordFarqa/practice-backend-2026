<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('room_id')->index('room_id');
            $table->dateTime('booking_start');
            $table->dateTime('booking_end');
            $table->unsignedInteger('user_id')->index('user_id');
            $table->unsignedInteger('status_id')->index('status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_rooms');
    }
};
