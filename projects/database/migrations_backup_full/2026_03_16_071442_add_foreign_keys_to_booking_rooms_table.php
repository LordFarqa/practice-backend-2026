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
        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->foreign(['room_id'], 'booking_rooms_ibfk_1')->references(['id'])->on('rooms')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['user_id'], 'booking_rooms_ibfk_2')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['status_id'], 'booking_rooms_ibfk_3')->references(['id'])->on('booking_statuses')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_rooms', function (Blueprint $table) {
            $table->dropForeign('booking_rooms_ibfk_1');
            $table->dropForeign('booking_rooms_ibfk_2');
            $table->dropForeign('booking_rooms_ibfk_3');
        });
    }
};
