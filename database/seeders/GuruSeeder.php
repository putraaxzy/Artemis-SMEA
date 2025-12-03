<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuruSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guruData = [
            [
                'username' => 'pak_miftah',
                'name' => 'Muhammad Miftahurrohman S.Pd',
                'password' => 'password123'
            ]
        ];

        foreach ($guruData as $guru) {
            User::create([
                'username' => $guru['username'],
                'name' => $guru['name'],
                'telepon' => null,
                'password' => Hash::make($guru['password']),
                'role' => 'guru',
                'kelas' => null,
                'jurusan' => null,
            ]);
        }

        $this->command->info('data seed guru telah berhasil ditambahkan');
    }
}
