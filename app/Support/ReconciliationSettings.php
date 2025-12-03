<?php

namespace App\Support;

use App\Models\ReconciliationSetting;

class ReconciliationSettings
{
    private const DEFAULT_RULES = "Conciliar: cuando el monto y la cuenta coinciden y la diferencia es menor o igual al umbral configurado. La fecha del voucher debe estar dentro del período del extracto.".PHP_EOL.PHP_EOL.
        "Rechazar: si la operación pertenece a otra cuenta, faltan datos del estudiante o la diferencia supera el umbral de faltante aun después de corroborar la documentación.".PHP_EOL.PHP_EOL.
        "Demasía: si el voucher es mayor al extracto y el excedente no supera el límite de crédito permitido; el saldo excedente se acredita automáticamente al estudiante.";

    private const DEFAULT_TEMPLATE = "student_code,operation_number,amount,bank_code,account_number,paid_at,status,payment_type".PHP_EOL.
        "juan.perez@unimatch.local,OP1001,500.00,BNB,96356224,2025-11-01,recibido,Transferencia".PHP_EOL.
        "# amount usa punto decimal, la moneda se asume BOB, status permitido: recibido, conciliado, rechazado, demasía.";

    private ?ReconciliationSetting $cache = null;

    public function current(): ReconciliationSetting
    {
        if ($this->cache) {
            return $this->cache;
        }

        $this->cache = ReconciliationSetting::first() ?? ReconciliationSetting::create([
            'difference_alert_threshold' => 1.00,
            'shortage_alert_threshold' => 5.00,
            'credit_max_amount' => 500.00,
            'voucher_statuses' => ['recibido', 'conciliado', 'rechazado', 'demasía'],
            'voucher_rules' => self::DEFAULT_RULES,
            'voucher_template_help' => self::DEFAULT_TEMPLATE,
        ]);

        $needsSave = false;

        if (! $this->hasUpdatedRules($this->cache->voucher_rules)) {
            $this->cache->voucher_rules = self::DEFAULT_RULES;
            $needsSave = true;
        }

        if (! $this->hasUpdatedTemplate($this->cache->voucher_template_help)) {
            $this->cache->voucher_template_help = self::DEFAULT_TEMPLATE;
            $needsSave = true;
        }

        if ($needsSave) {
            $this->cache->save();
        }

        return $this->cache;
    }

    public function refresh(): ReconciliationSetting
    {
        $this->cache = ReconciliationSetting::first();

        return $this->current();
    }

    private function hasUpdatedRules(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return str_contains($value, 'Conciliar: cuando');
    }

    private function hasUpdatedTemplate(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return str_contains($value, 'student_code,operation_number');
    }
}
