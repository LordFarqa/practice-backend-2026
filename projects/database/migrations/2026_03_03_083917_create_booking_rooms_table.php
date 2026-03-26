<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained();
            $table->dateTime('booking_start');
            $table->dateTime('booking_end');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('status_id')->constrained('booking_statuses');
            $table->timestamps();
            
            // Индексы для оптимизации
            $table->index(['room_id', 'booking_start', 'booking_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_rooms');
    }
};