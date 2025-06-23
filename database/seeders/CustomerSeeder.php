<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::insert([
            [
                'nama' => 'PT. Maju Jaya',
                'kategori' => 'perusahaan',
                'alamat' => 'Jl. Industri No. 1, Jakarta',
                'website' => 'https://majujaya.co.id',
                'email' => 'info@majujaya.co.id',
                'kontak_1' => '081234567890',
                'kontak_1_nama' => 'Budi',
                'kontak_1_jabatan' => 'Manager',
                'kontak_2_nama' => 'Sari',
                'kontak_2' => '081298765432',
                'kontak_2_jabatan' => 'Direktur',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'CV. Kreatif Mandiri',
                'kategori' => 'perusahaan',
                'alamat' => 'Jl. Kreatif No. 2, Bandung',
                'website' => 'https://kreatifmandiri.com',
                'email' => 'admin@kreatifmandiri.com',
                'kontak_1' => '082112223333',
                'kontak_1_nama' => 'Andi',
                'kontak_1_jabatan' => 'Owner',
                'kontak_2_nama' => 'Rina',
                'kontak_2' => '082144455555',
                'kontak_2_jabatan' => 'Marketing',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama' => 'Dewi Lestari',
                'kategori' => 'pribadi',
                'alamat' => 'Jl. Melati No. 3, Surabaya',
                'website' => null,
                'email' => 'dewi.lestari@gmail.com',
                'kontak_1' => '085612345678',
                'kontak_1_nama' => 'Dewi Lestari',
                'kontak_1_jabatan' => 'Owner',
                'kontak_2_nama' => null,
                'kontak_2' => null,
                'kontak_2_jabatan' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 