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
        $nom    = fake()->lastName();
        $prenom = fake()->firstName();

        return [
            'name'                   => "{$nom} {$prenom}",
            'nom'                    => $nom,
            'prenom'                 => $prenom,
            'email'                  => fake()->unique()->safeEmail(),
            'email_verified_at'      => now(),
            'password'               => static::$password ??= Hash::make('password'),
            'remember_token'         => Str::random(10),
            'statut'                 => 'ACTIF',
            'type_compte'            => 'CANAM',
            'telephone'              => fake()->phoneNumber(),
            'failed_login_attempts'  => 0,
            'must_change_password'   => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
