<?php
declare(strict_types=1);
namespace App\Services\Personnel;
use App\Models\Personnel;use App\Models\Role;
class PersonnelRoleSuggestionService
{
 public function suggest(Personnel $personnel):?Role{$position=str($personnel->position)->lower()->toString();$slug=match(true){str_contains($position,'kepala madrasah')=>'kepala-madrasah',str_contains($position,'guru')=>'guru',str_contains($position,'operator')=>'operator',str_contains($position,'tukang sapu'),str_contains($position,'petugas kebersihan')=>'tukang-sapu',default=>null};return $slug?Role::where('name',$slug)->where('guard_name','web')->first():null;}
}
