<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesBookEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'legacy_number',
        'invoice_date',
        'invoice_number',
        'nit_ci',
        'razon_social',
        'student_name',
        'payment_type',
        'amount',
        'account_label',
        'state_label',
        'custom_id',
        'bank_name',
        'recorded_date',
        'raw_payload',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'recorded_date' => 'date',
        'amount' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
