<?php
declare(strict_types=1); namespace App\Models; use Illuminate\Database\Eloquent\Model;
class PayrollDisbursement extends Model { protected $guarded=[]; protected $hidden=['bank_account_snapshot']; protected function casts():array{return ['bank_account_snapshot'=>'encrypted:array','amount'=>'decimal:2','submitted_at'=>'datetime','completed_at'=>'datetime'];} }
