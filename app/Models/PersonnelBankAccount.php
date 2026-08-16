<?php
declare(strict_types=1); namespace App\Models; use Illuminate\Database\Eloquent\Model;
class PersonnelBankAccount extends Model { protected $guarded=[]; protected $hidden=['account_number']; protected function casts():array{return ['account_number'=>'encrypted','is_primary'=>'boolean','verified_at'=>'datetime'];} public function getMaskedAccountNumberAttribute():string{$v=(string)$this->account_number;return str_repeat('*',max(4,strlen($v)-4)).substr($v,-4);} }
