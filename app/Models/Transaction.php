<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_statement_line_id',
        'payment_voucher_id',
        'status',
        'notes',
        'matched_by',
        'matched_at',
        'difference_amount',
    ];

    protected $casts = [
        'matched_at' => 'datetime',
        'difference_amount' => 'decimal:2',
    ];

    public function line()
    {
        return $this->belongsTo(BankStatementLine::class, 'bank_statement_line_id');
    }

    public function voucher()
    {
        return $this->belongsTo(PaymentVoucher::class, 'payment_voucher_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function getStudentAttribute()
    {
        if ($this->voucher && $this->voucher->relationLoaded('student')) {
            return $this->voucher->student;
        }

        if ($this->voucher && $this->voucher->student) {
            return $this->voucher->student;
        }

        if ($this->invoice && $this->invoice->relationLoaded('student')) {
            return $this->invoice->student;
        }

        return $this->invoice?->student;
    }

    public function matchedBy()
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    public function logs()
    {
        return $this->hasMany(TransactionLog::class);
    }
}
