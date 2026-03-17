<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddForeignKeysSafely extends Migration
{
    public function up()
    {
        // Получаем список существующих внешних ключей
        $existingForeignKeys = $this->getExistingForeignKeys();
        
        // Добавляем внешние ключи для booking_rooms
        $this->addForeignKeyIfNotExists(
            'booking_rooms', 
            'booking_rooms_room_id_foreign',
            'room_id', 
            'rooms', 
            'id',
            $existingForeignKeys
        );
        
        $this->addForeignKeyIfNotExists(
            'booking_rooms', 
            'booking_rooms_user_id_foreign',
            'user_id', 
            'users', 
            'id',
            $existingForeignKeys
        );
        
        $this->addForeignKeyIfNotExists(
            'booking_rooms', 
            'booking_rooms_status_id_foreign',
            'status_id', 
            'booking_statuses', 
            'id',
            $existingForeignKeys
        );
        
        // Добавляем внешние ключи для clients
        $this->addForeignKeyIfNotExists(
            'clients', 
            'clients_user_id_foreign',
            'user_id', 
            'users', 
            'id',
            $existingForeignKeys
        );
        
        $this->addForeignKeyIfNotExists(
            'clients', 
            'clients_role_id_foreign',
            'role_id', 
            'roles', 
            'id',
            $existingForeignKeys
        );
        
        // Добавляем внешние ключи для rooms
        $this->addForeignKeyIfNotExists(
            'rooms', 
            'rooms_hotel_id_foreign',
            'hotel_id', 
            'hotels', 
            'id',
            $existingForeignKeys
        );
        
        $this->addForeignKeyIfNotExists(
            'rooms', 
            'rooms_class_id_foreign',
            'class_id', 
            'room_classes', 
            'id',
            $existingForeignKeys
        );
        
        // Добавляем внешние ключи для reviews
        $this->addForeignKeyIfNotExists(
            'reviews', 
            'reviews_hotel_id_foreign',
            'hotel_id', 
            'hotels', 
            'id',
            $existingForeignKeys
        );
        
        $this->addForeignKeyIfNotExists(
            'reviews', 
            'reviews_booking_room_id_foreign',
            'booking_room_id', 
            'booking_rooms', 
            'id',
            $existingForeignKeys
        );
    }

    public function down()
    {
        // Можно оставить пустым или удалить добавленные ключи
    }

    private function getExistingForeignKeys()
    {
        $results = DB::select("
            SELECT 
                CONSTRAINT_NAME,
                TABLE_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        $keys = [];
        foreach ($results as $row) {
            $keys[$row->TABLE_NAME][] = $row->CONSTRAINT_NAME;
        }
        
        return $keys;
    }

    private function addForeignKeyIfNotExists($table, $constraintName, $column, $referencedTable, $referencedColumn, $existingKeys)
    {
        // Проверяем, существует ли уже такой внешний ключ
        if (isset($existingKeys[$table]) && in_array($constraintName, $existingKeys[$table])) {
            echo "Foreign key {$constraintName} already exists on {$table}\n";
            return;
        }
        
        // Проверяем, существует ли колонка и таблица
        if (!Schema::hasTable($table) || !Schema::hasTable($referencedTable)) {
            echo "Table {$table} or {$referencedTable} does not exist\n";
            return;
        }
        
        if (!Schema::hasColumn($table, $column)) {
            echo "Column {$column} does not exist in table {$table}\n";
            return;
        }
        
        try {
            Schema::table($table, function (Blueprint $table) use ($column, $referencedTable, $referencedColumn) {
                $table->foreign($column)
                      ->references($referencedColumn)
                      ->on($referencedTable)
                      ->onDelete('restrict')
                      ->onUpdate('restrict');
            });
            echo "Added foreign key {$column} -> {$referencedTable}.{$referencedColumn}\n";
        } catch (\Exception $e) {
            echo "Error adding foreign key: " . $e->getMessage() . "\n";
        }
    }
}