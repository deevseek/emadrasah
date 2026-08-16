<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class LandingPageSetting extends Model {protected $guarded=[]; public static function values():array{return static::query()->pluck('value','key')->all();}}
