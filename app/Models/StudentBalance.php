<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'currency',
        'balance_amount',
    ];

    protected $casts = [
        'balance_amount' => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
