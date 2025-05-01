<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Ghercy Segovia',
            'code' => 'GS',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);


        User::create([
            'code' => 'CH',
            'name' => 'Christell',
            'email' => 'christell@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'code' => 'CR',
            'name' => 'Carlos Rojas',
            'email' => 'carlos@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'code' => 'FS',
            'name' => 'Fhiona Segovia',
            'email' => 'fhiona@example.com',
            'password' => Hash::make('password'),
            'role' => 'supervisor'
        ]);

        User::create([
            'code' => 'MC',
            'name' => 'Maly Carvajal',
            'email' => 'maly@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'code' => 'OO',
            'name' => 'Omar Ostos',
            'email' => 'omar@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'code' => 'RM',
            'name' => 'Raul Medrano',
            'email' => 'raul@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'code' => 'RS',
            'name' => 'Ricardo Segovia',
            'email' => 'ricardo@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'code' => 'AG',
            'name' => 'AG',
            'email' => 'ag@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        

        

        

        

        
    }
}
