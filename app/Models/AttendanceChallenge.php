<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;use Illuminate\Database\Eloquent\Model;
class AttendanceChallenge extends Model{use HasUuids;protected $guarded=[];protected function casts():array{return['expires_at'=>'datetime','used_at'=>'datetime'];}}
