<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingStatusesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('booking_statuses')) {
            Schema::create('booking_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('booking_statuses');
    }
}