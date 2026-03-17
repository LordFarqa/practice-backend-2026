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
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreign(['hotel_id'], 'rooms_ibfk_1')->references(['id'])->on('hotels')->onUpdate('restrict')->onDelete('cascade');
            $table->foreign(['class_id'], 'rooms_ibfk_2')->references(['id'])->on('room_classes')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign('rooms_ibfk_1');
            $table->dropForeign('rooms_ibfk_2');
        });
    }
};
