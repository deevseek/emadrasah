<?php
declare(strict_types=1); namespace App\Models\Finance; use Illuminate\Database\Eloquent\Model;
class StudentInvoice extends Model { protected $table='student_invoices'; protected $guarded=[]; protected function casts():array{return ['billing_month'=>'date','issue_date'=>'date','due_date'=>'date','subtotal'=>'decimal:2','discount'=>'decimal:2','penalty'=>'decimal:2','total'=>'decimal:2','paid_amount'=>'decimal:2','outstanding_amount'=>'decimal:2'];} }
