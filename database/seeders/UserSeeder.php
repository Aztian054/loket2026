<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator Sistem',
                'username' => 'admin',
                'email' => 'admin@loket.balam.go.id',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ],
            [
                'name' => 'Petugas Loket Pelayanan',
                'username' => 'loket1',
                'email' => 'loket@loket.balam.go.id',
                'password' => Hash::make('loket123'),
                'role' => 'loket',
                'is_active' => true,
            ],
            [
                'name' => 'Petugas Verifikator Berkas',
                'username' => 'verifikator1',
                'email' => 'verifikator@loket.balam.go.id',
                'password' => Hash::make('verif123'),
                'role' => 'verifikator',
                'is_active' => true,
            ],
            [
                'name' => 'Petugas Arsip Warkah',
                'username' => 'warkah1',
                'email' => 'warkah@loket.balam.go.id',
                'password' => Hash::make('warkah123'),
                'role' => 'warkah',
                'is_active' => true,
            ],
            [
                'name' => 'Petugas Validator Data',
                'username' => 'validator1',
                'email' => 'validator@loket.balam.go.id',
                'password' => Hash::make('valid123'),
                'role' => 'validator',
                'is_active' => true,
            ],
            [
                'name' => 'Validator Pra-BTel',
                'username' => 'validator_btel',
                'email' => 'validator_btel@loket.balam.go.id',
                'password' => Hash::make('validbtel123'),
                'role' => 'validator_btel',
                'is_active' => true,
            ],
            [
                'name' => 'Validator Pra-SuEl',
                'username' => 'validator_suel',
                'email' => 'validator_suel@loket.balam.go.id',
                'password' => Hash::make('validsuel123'),
                'role' => 'validator_suel',
                'is_active' => true,
            ],
            [
                'name' => 'Petugas Alih Media',
                'username' => 'alih1',
                'email' => 'alihmedia@loket.balam.go.id',
                'password' => Hash::make('alih123'),
                'role' => 'alih_media',
                'is_active' => true,
            ],
            [
                'name' => 'Alih Media Pra-BTel',
                'username' => 'alih_media_btel',
                'email' => 'alihmedia_btel@loket.balam.go.id',
                'password' => Hash::make('alihbtel123'),
                'role' => 'alih_media_btel',
                'is_active' => true,
            ],
            [
                'name' => 'Alih Media Pra-SuEl',
                'username' => 'alih_media_suel',
                'email' => 'alihmedia_suel@loket.balam.go.id',
                'password' => Hash::make('alihsuel123'),
                'role' => 'alih_media_suel',
                'is_active' => true,
            ],
            [
                'name' => 'Petugas Pembayaran',
                'username' => 'pembayaran1',
                'email' => 'pembayaran@loket.balam.go.id',
                'password' => Hash::make('bayar123'),
                'role' => 'pembayaran',
                'is_active' => true,
            ],
            [
                'name' => 'Kepala Kantor Pertanahan',
                'username' => 'pimpinan',
                'email' => 'pimpinan@loket.balam.go.id',
                'password' => Hash::make('pimpinan123'),
                'role' => 'pimpinan',
                'is_active' => true,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['username' => $user['username']], $user);
        }
    }
}
