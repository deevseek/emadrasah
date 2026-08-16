<?php
namespace App\Models; use App\Enums\LandingNewsStatus; use Illuminate\Database\Eloquent\Model;
class LandingNews extends Model {protected $table='landing_news';protected $guarded=[];protected function casts():array{return ['published_at'=>'datetime','featured'=>'boolean','status'=>LandingNewsStatus::class];}public function scopePublished($q){return $q->where('status','published')->whereNotNull('published_at')->where('published_at','<=',now());}}
