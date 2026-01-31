<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Bet;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'creator_id' => User::factory(),
            'type' => $this->faker->randomElement(['deposit', 'withdrawal', 'bet_cost', 'bet_refund']),
            'amount' => $this->faker->randomFloat(2, -50, 50),
            'description' => $this->faker->sentence(),
            'bet_id' => null,
        ];
    }

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deposit',
            'amount' => $this->faker->randomFloat(2, 10, 100),
            'description' => 'Deposit',
        ]);
    }

    public function betCost(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'bet_cost',
            'amount' => -$this->faker->randomFloat(2, 0.30, 1.00),
            'bet_id' => Bet::factory(),
            'description' => 'Bet cost',
        ]);
    }
}
