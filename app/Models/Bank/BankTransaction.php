<?php
declare(strict_types=1); namespace App\Models\Bank; use Illuminate\Database\Eloquent\Model;
class BankTransaction extends Model { protected $guarded=[]; protected function casts():array{return ['amount'=>'decimal:2','occurred_at'=>'datetime','reconciled_at'=>'datetime','raw_payload'=>'encrypted:array'];} }
