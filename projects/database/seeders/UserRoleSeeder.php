<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Role;
use Illuminate\Database\Seeder;


class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'name'=>'user'
            ],
            [
                'name'=>'admin'
            ]
        ];
        foreach($statuses as $status){
            Role::firstOrCreate($status);
        }
    }
}
