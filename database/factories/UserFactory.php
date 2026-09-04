<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            // users.role_id is NOT NULL, so every factory user needs one.
            // Defaults to visitor; use ->role('admin') for anything else.
            'role_id' => fn () => self::roleId('visitor'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Build the user with a specific role.
     *
     *     User::factory()->role('ferry_operator')->create();
     *
     * @param  string  $name  One of the five seeded roles.name values.
     */
    public function role(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => self::roleId($name),
        ]);
    }

    /**
     * Find the role by machine name, creating it if the seeder has not run.
     * Tests using RefreshDatabase start from an empty table, so this keeps a
     * test from having to seed the whole application first.
     */
    private static function roleId(string $name): int
    {
        return Role::firstOrCreate(
            ['name' => $name],
            ['label' => Str::headline($name)],
        )->id;
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
