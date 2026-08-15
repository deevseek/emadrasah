<?php
declare(strict_types=1);namespace App\Models;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PersonnelFaceSample extends Model{protected $guarded=[];protected $hidden=['embedding','photo_path'];protected function casts():array{return['embedding'=>'encrypted:array','quality_score'=>'float'];}public function profile():BelongsTo{return$this->belongsTo(PersonnelFaceProfile::class,'personnel_face_profile_id');}}
