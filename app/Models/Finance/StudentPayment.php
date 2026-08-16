<?php
declare(strict_types=1); namespace App\Models\Finance; use Illuminate\Database\Eloquent\Model;
class StudentPayment extends Model { protected $table='student_payments'; protected $guarded=[]; protected function casts():array{return ['amount'=>'decimal:2','paid_at'=>'datetime','metadata'=>'array'];} public function student(){return $this->belongsTo(\App\Models\Student::class);} public function invoice(){return $this->belongsTo(StudentInvoice::class);} public function creator(){return $this->belongsTo(\App\Models\User::class,'created_by');} }
