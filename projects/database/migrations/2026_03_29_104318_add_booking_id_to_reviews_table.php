<?php
// database/migrations/2026_03_29_000001_add_booking_id_to_reviews_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBookingIdToReviewsTable extends Migration
{
    public function up()
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Проверяем, существует ли колонка
            if (!Schema::hasColumn('reviews', 'booking_id')) {
                $table->unsignedBigInteger('booking_id')->nullable()->after('user_id');
                $table->foreign('booking_id')
                    ->references('id')
                    ->on('booking_rooms')
                    ->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn('booking_id');
        });
    }
}