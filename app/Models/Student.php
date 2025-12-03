<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'full_name',
        'email',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function balances()
    {
        return $this->hasMany(StudentBalance::class);
    }

    public function vouchers()
    {
        return $this->hasMany(PaymentVoucher::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
