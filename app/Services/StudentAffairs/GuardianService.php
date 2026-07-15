<?php

declare(strict_types=1);
namespace App\Services\StudentAffairs;
use App\Models\Guardian; use App\Services\ActivityLogger; use Illuminate\Support\Facades\DB;
class GuardianService { public function __construct(private ActivityLogger $logger) {} public function save(array $data, ?Guardian $guardian=null): Guardian { return DB::transaction(function() use($data,$guardian){ $guardian ??= new Guardian; $old=$guardian->exists?$guardian->getOriginal():[]; $data['is_active']=$data['is_active']??true; $guardian->fill($data)->save(); $this->logger->log($old?'guardian.updated':'guardian.created',$guardian,$old,$guardian->getAttributes(),'Data wali disimpan.'); return $guardian; }); } }
