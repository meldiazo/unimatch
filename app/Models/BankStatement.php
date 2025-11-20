<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatement extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'bank_account_id',
        'import_batch_id',
        'statement_date',
        'currency',
        'opening_balance',
        'closing_balance',
        'status',
        'meta',
    ];

    protected $casts = [
        'statement_date' => 'date',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'meta' => 'array',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function account()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function lines()
    {
        return $this->hasMany(BankStatementLine::class);
    }
}
