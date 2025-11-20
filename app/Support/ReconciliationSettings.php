<?php

namespace App\Support;

use App\Models\ReconciliationSetting;

class ReconciliationSettings
{
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
            'voucher_statuses' => ['recibido', 'validado', 'rechazado', 'demasía'],
            'voucher_rules' => 'Validar montos, fechas y documentos adjuntos antes de aprobar.',
            'voucher_template_help' => 'Incluye: código estudiante, banco, monto, fecha y comprobante legible.',
        ]);

        return $this->cache;
    }

    public function refresh(): ReconciliationSetting
    {
        $this->cache = ReconciliationSetting::first();

        return $this->current();
    }
}
