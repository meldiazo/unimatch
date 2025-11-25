<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_code',
        'status',
        'format_config',
    ];

    protected $casts = [
        'format_config' => 'array',
    ];

    public function accounts()
    {
        return $this->hasMany(BankAccount::class);
    }

    public function statements()
    {
        return $this->hasManyThrough(
            BankStatement::class,
            BankAccount::class,
            'bank_id',
            'bank_account_id',
            'id',
            'id'
        );
    }

    public function vouchers()
    {
        return $this->hasManyThrough(
            PaymentVoucher::class,
            BankAccount::class,
            'bank_id',
            'bank_account_id',
            'id',
            'id'
        );
    }
}
