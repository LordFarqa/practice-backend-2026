<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'Администратор'],
            ['id' => 2, 'name' => 'Пользователь'],
            ['id' => 3, 'name' => 'Персонал']
        ];
        
        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                ['name' => $role['name']]
            );
        }
    }
}