<?php

namespace App\Support;

use App\Models\SalesBookEntry;
use App\Models\Student;
use Illuminate\Support\Str;

class StudentResolver
{
    /**
     * Encuentra o crea (opcionalmente) un estudiante a partir de un registro del reporte diario.
     */
    public static function resolveFromEntry(SalesBookEntry $entry, bool $autoCreate = false): ?Student
    {
        $identifier = self::normalizeIdentifier($entry->custom_id ?? null);
        $emailIdentifier = self::normalizeIdentifier($entry->raw_payload['student_code'] ?? null);

        if ($identifier) {
            $student = Student::where('code', $identifier)->first();
            if ($student) {
                return $student;
            }
        }

        if ($emailIdentifier) {
            $student = Student::where('email', $emailIdentifier)->first();
            if ($student) {
                return $student;
            }
        }

        if ($entry->student_name) {
            $student = Student::where('full_name', $entry->student_name)->first();
            if ($student) {
                return $student;
            }
        }

        if (! $autoCreate) {
            return null;
        }

        $code = $identifier ?: $emailIdentifier;
        if (! $code) {
            $code = 'AUTO-'.$entry->id;
        }

        $name = $entry->student_name
            ?? $entry->razon_social
            ?? $code;

        return Student::updateOrCreate(
            ['code' => $code],
            [
                'full_name' => $name,
                'email' => $emailIdentifier,
            ]
        );
    }

    private static function normalizeIdentifier(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        return Str::upper($trimmed);
    }
}
