<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatementLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_statement_id',
        'line_number',
        'operation_number',
        'reference',
        'description',
        'operation_date',
        'value_date',
        'amount',
        'currency',
        'running_balance',
        'raw_payload',
    ];

    protected $casts = [
        'operation_date' => 'date',
        'value_date' => 'date',
        'amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function statement()
    {
        return $this->belongsTo(BankStatement::class, 'bank_statement_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }
}
