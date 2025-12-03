<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('20210###'),
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'meta' => [
                'document' => $this->faker->numerify('######## LP'),
            ],
        ];
    }
}
