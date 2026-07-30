<?php
declare(strict_types=1); namespace App\Http\Requests\Personnel;
class UpdatePersonnelRequest extends StorePersonnelRequest { public function authorize():bool{return $this->user()->can('personnel.update');} public function rules():array{return $this->rulesFor((int)$this->route('personnel')->id);} }
