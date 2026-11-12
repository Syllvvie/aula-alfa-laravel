<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Nunca cria administrador fora do ambiente local.
        if (! app()->environment('local')) {
            return;
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@local.test'],
            [
                'name'     => 'Administrador Local',
                'password' => env('ADMIN_SEED_PASSWORD', 'admin-local-123'),
            ]
        );

        Profile::updateOrCreate(
            ['user_id' => $admin->id],
            ['type' => 'admin']
        );
    }
}