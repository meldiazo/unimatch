<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'account_number',
        'currency',
        'active',
        'meta',
    ];

    protected $casts = [
        'active' => 'boolean',
        'meta' => 'array',
    ];

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function statements()
    {
        return $this->hasMany(BankStatement::class);
    }

    public function vouchers()
    {
        return $this->hasMany(PaymentVoucher::class);
    }
}
