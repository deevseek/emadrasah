<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class StudentPayment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payment_date' => 'date',
        'cancelled_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function allocations()
    {
        return $this->hasMany(StudentPaymentAllocation::class);
    }

    public function financialTransaction()
    {
        return $this->belongsTo(FinancialTransaction::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
