<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder awal tanpa data dummy operasional (mulai dari 0).
 *
 * Akun (kata sandi: password):
 *   pengelola@aquakelola.test  -> Pengelola (CRUD penuh)
 *   pemilik@aquakelola.test    -> Mitra / Pemilik (hanya lihat)
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'name' => 'Andi Rahman',
            'email' => 'pengelola@aquakelola.test',
            'password' => Hash::make('password'),
            'role' => 'pengelola',
        ]);

        User::create([
            'name' => 'Budi Santoso',
            'email' => 'pemilik@aquakelola.test',
            'password' => Hash::make('password'),
            'role' => 'mitra_pemilik',
        ]);
    }
}