<?php

namespace Database\Seeders;
use App\Models\Role;
use Illuminate\Database\Seeder;


class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Клиент'],
            ['name' => 'Администратор'],
            ['name'=> 'Персонал']
        ];
        foreach($roles as $role){
            Role::firstOrCreate($role);
        }
    }
}
