<?php
declare(strict_types=1); namespace App\Models; use Illuminate\Database\Eloquent\Model;
class StudentLeaveRequestTransition extends Model { public $timestamps=false; protected $guarded=[]; protected function casts():array{return ['transitioned_at'=>'datetime'];} }
