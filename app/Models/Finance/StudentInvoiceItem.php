<?php
declare(strict_types=1); namespace App\Models\Finance; use Illuminate\Database\Eloquent\Model;
class StudentInvoiceItem extends Model { protected $table='student_invoice_items'; protected $guarded=[]; protected function casts():array{return ['unit_amount'=>'decimal:2','amount'=>'decimal:2'];} public function invoice(){return $this->belongsTo(StudentInvoice::class,'invoice_id');} public function feeType(){return $this->belongsTo(FeeType::class);} }
