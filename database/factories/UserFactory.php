<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => bcrypt('password'), // jelszó minden usernek: password
            'remember_token' => Str::random(10),
            'role' => 'user', // alap user, admin a seederben külön
        ];
    }

    // Admin állapot
    public function admin()
    {
        return $this->state(fn () => ['role' => 'admin']);
    }
}
