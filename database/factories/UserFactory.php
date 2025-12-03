<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'telepon' => fake()->unique()->phoneNumber(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'siswa',
            'kelas' => fake()->randomElement(['X', 'XI', 'XII']),
            'jurusan' => fake()->randomElement(['MPLB', 'RPL', 'PM', 'TKJ', 'AKL']),
        ];
    }

    /**
     * State for guru role.
     */
    public function guru(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'guru',
            'kelas' => null,
            'jurusan' => null,
        ]);
    }
}
