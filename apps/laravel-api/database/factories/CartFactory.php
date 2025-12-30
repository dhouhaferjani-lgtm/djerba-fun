<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_id' => Str::uuid()->toString(),
            'status' => Cart::STATUS_ACTIVE,
            'expires_at' => now()->addMinutes(Cart::DEFAULT_EXPIRATION_MINUTES),
        ];
    }

    /**
     * Indicate that the cart belongs to an authenticated user.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the cart is checking out.
     */
    public function checkingOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cart::STATUS_CHECKING_OUT,
        ]);
    }

    /**
     * Indicate that the cart is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cart::STATUS_COMPLETED,
        ]);
    }

    /**
     * Indicate that the cart is abandoned.
     */
    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Cart::STATUS_ABANDONED,
        ]);
    }

    /**
     * Indicate that the cart has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(5),
        ]);
    }
}
