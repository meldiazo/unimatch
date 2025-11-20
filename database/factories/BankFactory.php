<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankFactory extends Factory
{
    protected $model = Bank::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company . ' Banco';

        return [
            'name' => $name,
            'short_code' => strtoupper($this->faker->unique()->lexify('BNK???')),
            'status' => 'active',
            'format_config' => [
                'columns' => [
                    'operation_number' => 'NRO_OPERACION',
                    'amount' => 'MONTO',
                    'operation_date' => 'FECHA_OPERACION',
                ],
                'date_format' => 'Y-m-d',
            ],
        ];
    }
}
