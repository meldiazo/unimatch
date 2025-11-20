<?php

namespace Database\Factories;

use App\Models\Bank;
use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'bank_id' => Bank::factory(),
            'account_number' => $this->faker->unique()->bankAccountNumber,
            'currency' => 'BOB',
            'active' => true,
            'meta' => [
                'type' => $this->faker->randomElement(['corriente', 'ahorro']),
            ],
        ];
    }
}
