<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixReviewsTable extends Migration
{
    public function up()
    {
        // Добавляем колонку rating после coment
        if (!Schema::hasColumn('reviews', 'rating')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->tinyInteger('rating')->default(5)->after('coment');
            });
        }
        
        // НЕ переименовываем coment, так как модель уже использует 'coment'
        // Просто убедимся, что в модели используется правильное название
    }

    public function down()
    {
        if (Schema::hasColumn('reviews', 'rating')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropColumn('rating');
            });
        }
    }
}