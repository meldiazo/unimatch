<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconciliationSetting extends Model
{
    protected $fillable = [
        'difference_alert_threshold',
        'shortage_alert_threshold',
        'credit_max_amount',
        'voucher_statuses',
        'voucher_rules',
        'voucher_template_help',
    ];

    protected $casts = [
        'voucher_statuses' => 'array',
    ];
}
