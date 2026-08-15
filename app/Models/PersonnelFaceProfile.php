<?php
declare(strict_types=1);namespace App\Models;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class PersonnelFaceProfile extends Model{protected $guarded=[];protected function casts():array{return['registered_at'=>'datetime','last_verified_at'=>'datetime'];}public function personnel():BelongsTo{return$this->belongsTo(Personnel::class);}public function samples():HasMany{return$this->hasMany(PersonnelFaceSample::class);}public function registeredBy():BelongsTo{return$this->belongsTo(User::class,'registered_by');}}
