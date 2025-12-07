<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\User;
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
        User::query()->delete();

        User::updateOrCreate(
            ['email' => 'jefe@unimatch.local'],
            [
                'name' => 'Norma Paris',
                'role' => User::ROLE_JEFE_CONTABILIDAD,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'ingresos@unimatch.local'],
            [
                'name' => 'Nataly Quinteros',
                'role' => User::ROLE_ENCARGADO_INGRESOS,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'cajero@unimatch.local'],
            [
                'name' => 'Gustavo Jiménez',
                'role' => User::ROLE_CAJERO,
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Catálogo de bancos y cuentas base
        $banks = [
            [
                'name' => 'Banco Económico',
                'short_code' => 'BE',
                'accounts' => ['61543334149257174', '56042479'],
            ],
            [
                'name' => 'BCP',
                'short_code' => 'BCP',
                'accounts' => ['960001234567', '960001234568'],
            ],
            [
                'name' => 'Banco Bisa',
                'short_code' => 'BISA',
                'accounts' => ['735919541'],
            ],
            [
                'name' => 'Banco Nacional de Bolivia',
                'short_code' => 'BNB',
                'accounts' => ['96356224', '8906345455860'],
            ],
            [
                'name' => 'Banco Mercantil Santa Cruz',
                'short_code' => 'BMSC',
                'accounts' => ['0027419886647'],
            ],
            [
                'name' => 'Banco Unión',
                'short_code' => 'BNI',
                'accounts' => ['40209431488476'],
            ],
        ];

        foreach ($banks as $data) {
            $accounts = $data['accounts'] ?? [];
            unset($data['accounts']);

            $bank = Bank::updateOrCreate(
                ['short_code' => $data['short_code']],
                $data + [
                    'status' => 'active',
                    'format_config' => [
                        'columns' => [
                            'operation_number' => 'operation_number',
                            'amount' => 'amount',
                            'operation_date' => 'operation_date',
                            'value_date' => 'value_date',
                            'description' => 'description',
                        ],
                        'date_format' => 'Y-m-d',
                    ],
                ]
            );

            if ($accounts) {
                foreach ($accounts as $number) {
                    BankAccount::updateOrCreate(
                        [
                            'bank_id' => $bank->id,
                            'account_number' => $number,
                        ],
                        [
                            'currency' => 'BOB',
                            'active' => true,
                            'meta' => ['type' => 'corriente'],
                        ]
                    );
                }
            } else {
                BankAccount::factory()
                    ->count(2)
                    ->create([
                        'bank_id' => $bank->id,
                    ]);
            }
        }

        $students = [
            ['code' => 'STU2025001', 'full_name' => 'Juan Pérez', 'email' => 'juan.perez@unimatch.local', 'document' => '7896541 LP'],
            ['code' => 'STU2025002', 'full_name' => 'Ana Gómez', 'email' => 'ana.gomez@unimatch.local', 'document' => '8456123 CB'],
            ['code' => 'STU2025003', 'full_name' => 'Luis Rojas', 'email' => 'luis.rojas@unimatch.local', 'document' => '5647382 SC'],
            ['code' => 'STU2025004', 'full_name' => 'María López', 'email' => 'maria.lopez@unimatch.local', 'document' => '9283746 LP'],
            ['code' => 'STU2025005', 'full_name' => 'Carlos Díaz', 'email' => 'carlos.diaz@unimatch.local', 'document' => '7348291 CB'],
            ['code' => 'STU2025006', 'full_name' => 'Sofía Maldonado', 'email' => 'tesoreria@unimatch.local', 'document' => '7000456 LP'],
            ['code' => 'STU2025007', 'full_name' => 'José Herrera', 'email' => 'jose.herrera@unimatch.local', 'document' => '6781234 SC'],
            ['code' => 'STU2025008', 'full_name' => 'Andrea Ruiz', 'email' => 'andrea.ruiz@unimatch.local', 'document' => '5678123 LP'],
            ['code' => 'STU2025009', 'full_name' => 'Gabriela Núñez', 'email' => 'gabriela.nunez@unimatch.local', 'document' => '4567812 CB'],
            ['code' => 'STU2025010', 'full_name' => 'Ricardo Fuentes', 'email' => 'ricardo.fuentes@unimatch.local', 'document' => '8123456 LP'],
        ];

        foreach ($students as $record) {
            $student = Student::updateOrCreate(
                ['code' => $record['code']],
                [
                    'full_name' => $record['full_name'],
                    'email' => $record['email'],
                    'meta' => [
                        'document' => $record['document'],
                    ],
                ]
            );

            User::updateOrCreate(
                ['email' => $record['email']],
                [
                    'name' => $record['full_name'],
                    'role' => User::ROLE_ESTUDIANTE,
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
