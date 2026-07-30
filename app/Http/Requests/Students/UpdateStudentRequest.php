<?php
declare(strict_types=1); namespace App\Http\Requests\Students;
class UpdateStudentRequest extends StoreStudentRequest { public function authorize():bool{return $this->user()->can('students.update');} public function rules():array{return $this->rulesFor($this->route('student')->id);} }
