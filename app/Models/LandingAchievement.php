<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class LandingAchievement extends Model {protected $table='landing_achievements'; protected $guarded=[]; protected function casts():array{return ['is_active'=>'boolean','featured'=>'boolean','date'=>'date'];} public function scopeActive($q){return $q->where('is_active',true);} }
