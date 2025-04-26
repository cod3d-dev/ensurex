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
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin'
        ]);

        User::create([
            'name' => 'AG',
            'email' => 'ag@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'name' => 'Christell',
            'email' => 'christell@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'name' => 'Carlos Rojas',
            'email' => 'carlos@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'name' => 'Fhiona Segovia',
            'email' => 'fhiona@example.com',
            'password' => Hash::make('password'),
            'role' => 'supervisor'
        ]);

        User::create([
            'name' => 'Maly Carvajal',
            'email' => 'maly@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'name' => 'Omar Ostos',
            'email' => 'omar@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'name' => 'Raul Medrano',
            'email' => 'raul@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        User::create([
            'name' => 'Ricardo Segovia',
            'email' => 'ricardo@example.com',
            'password' => Hash::make('password'),
            'role' => 'agent'
        ]);

        

        

        

        

        
    }
}
