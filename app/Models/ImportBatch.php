<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_type',
        'source_name',
        'uploaded_by',
        'uploaded_at',
        'status',
        'summary_data',
        'errors',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'summary_data' => 'array',
        'errors' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function bankStatements()
    {
        return $this->hasMany(BankStatement::class);
    }

    public function voucherBatches()
    {
        return $this->hasMany(VoucherBatch::class);
    }
}
