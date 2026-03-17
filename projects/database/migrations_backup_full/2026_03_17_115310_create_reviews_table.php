<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained();
                $table->foreignId('booking_room_id')->constrained();
                $table->text('comment');
                $table->tinyInteger('rating')->default(5);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}