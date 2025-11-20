<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Student;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Norma Paris',
            'email' => 'jefe@unimatch.local',
            'role' => User::ROLE_JEFE_CONTABILIDAD,
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Nataly Quinteros',
            'email' => 'ingresos@unimatch.local',
            'role' => User::ROLE_ENCARGADO_INGRESOS,
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Ana Castillo',
            'email' => 'cajero@unimatch.local',
            'role' => User::ROLE_CAJERO,
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Estudiante Demo',
            'email' => 'estudiante@unimatch.local',
            'role' => User::ROLE_ESTUDIANTE,
            'password' => bcrypt('password'),
        ]);

        $banks = [
            ['name' => 'Banco Nacional de Bolivia', 'short_code' => 'BNB'],
            ['name' => 'Banco Económico', 'short_code' => 'BE'],
            ['name' => 'Banco Mercantil Santa Cruz', 'short_code' => 'BMSC'],
            ['name' => 'Banco Bisa', 'short_code' => 'BISA'],
            ['name' => 'Banco Sol', 'short_code' => 'BSOL'],
            ['name' => 'Banco Unión', 'short_code' => 'BUNI'],
        ];

        foreach ($banks as $data) {
            $bank = Bank::create($data + [
                'status' => 'active',
                'format_config' => [
                    'columns' => [
                        'operation_number' => 'operation_number',
                        'amount' => 'amount',
                        'operation_date' => 'operation_date',
                        'value_date' => 'value_date',
                        'reference' => 'reference',
                        'description' => 'description',
                    ],
                    'date_format' => 'Y-m-d',
                ],
            ]);

            BankAccount::factory()
                ->count(2)
                ->create([
                    'bank_id' => $bank->id,
                ]);
        }

        Student::factory(9)->create();

        Student::create([
            'code' => '20210001',
            'full_name' => 'Estudiante Demo',
            'program' => 'Contabilidad',
            'email' => 'estudiante@unimatch.local',
            'meta' => ['document' => '12345678 LP'],
        ]);

        User::factory(5)->create();
    }
}
