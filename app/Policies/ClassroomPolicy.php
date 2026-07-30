<?php
declare(strict_types=1); namespace App\Policies; use App\Models\{Classroom,User};
class ClassroomPolicy { public function viewAny(User $u):bool{return $u->can('classrooms.view')||$u->can('classrooms.view-own');} public function create(User $u):bool{return $u->can('classrooms.create');} public function update(User $u,Classroom $c):bool{return $u->can('classrooms.update');} public function view(User $u,Classroom $c):bool{return $u->can('classrooms.view')||($u->can('classrooms.view-own')&&$u->personnel?->id===$c->homeroom_personnel_id);} }
