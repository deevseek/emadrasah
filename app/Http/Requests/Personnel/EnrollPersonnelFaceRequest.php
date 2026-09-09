<?php
declare(strict_types=1);namespace App\Http\Requests\Personnel;use Illuminate\Foundation\Http\FormRequest;
class EnrollPersonnelFaceRequest extends FormRequest{public function authorize():bool{$p=$this->route('personnel');return$this->user()?->can($p->faceProfile?'personnel-face.replace':'personnel-face.enroll')===true;}public function rules():array{$rule=['required','image','mimes:jpeg','max:2048'];return['front_1'=>$rule,'front_2'=>$rule,'natural'=>$rule,'left'=>$rule,'right'=>$rule];}}
