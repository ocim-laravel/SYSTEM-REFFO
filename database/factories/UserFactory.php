<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Division;
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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    /**
     * Peran adalah milik spatie/laravel-permission; kolom `role` legacy sudah dihapus.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            if ($user->getRoleNames()->isEmpty()) {
                $this->assignRole($user, UserRole::Production);
            }
        });
    }

    public function forRole(UserRole $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            $this->assignRole($user, $role);
        });
    }

    public function division(?Division $division): static
    {
        return $this->state(fn (array $attributes): array => [
            'division_id' => $division?->id,
        ]);
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

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn (array $attributes): array => [
            'must_change_password' => true,
        ]);
    }

    private function assignRole(User $user, UserRole $role): void
    {
        $roleModel = config('permission.models.role');

        if ($roleModel::where('name', $role->value)->exists()) {
            $user->syncRoles([$role->value]);
        }
    }
}
