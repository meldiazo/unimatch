<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_batch_id',
    ];

    public function importBatch()
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function vouchers()
    {
        return $this->hasMany(PaymentVoucher::class);
    }
}
