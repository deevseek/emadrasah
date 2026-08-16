<?php
declare(strict_types=1); namespace App\Models\Finance; use Illuminate\Database\Eloquent\Model;
class StudentPayment extends Model { protected $table='student_payments'; protected $guarded=[]; protected function casts():array{return ['amount'=>'decimal:2','paid_at'=>'datetime','metadata'=>'array'];} }
