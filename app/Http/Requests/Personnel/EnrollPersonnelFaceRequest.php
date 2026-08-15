<?php
declare(strict_types=1);namespace App\Http\Requests\Personnel;use Illuminate\Foundation\Http\FormRequest;
class EnrollPersonnelFaceRequest extends FormRequest{public function authorize():bool{$p=$this->route('personnel');return$this->user()?->can($p->faceProfile?'personnel-face.replace':'personnel-face.enroll')===true;}public function rules():array{return['front'=>['required','image','mimes:jpeg,png','max:5120'],'left'=>['required','image','mimes:jpeg,png','max:5120'],'right'=>['required','image','mimes:jpeg,png','max:5120']];}}
