<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_batch_id',
        'student_id',
        'bank_id',
        'bank_account_id',
        'cashbox_number',
        'operation_number',
        'payment_type',
        'amount',
        'currency',
        'paid_at',
        'received_at',
        'account_reference',
        'status',
        'billing_status',
        'billed_at',
        'billed_by',
        'reason',
        'document_path',
        'document_mime',
        'raw_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
        'received_at' => 'date',
        'billed_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    protected $appends = [
        'document_url',
    ];

    public function getDocumentUrlAttribute(): ?string
    {
        if (! $this->document_path) {
            return null;
        }

        return \Storage::disk('public')->url($this->document_path);
    }

    public function batch()
    {
        return $this->belongsTo(VoucherBatch::class, 'voucher_batch_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function billedBy()
    {
        return $this->belongsTo(User::class, 'billed_by');
    }
}
