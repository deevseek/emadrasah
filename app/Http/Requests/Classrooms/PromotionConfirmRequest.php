<?php
declare(strict_types=1); namespace App\Http\Requests\Classrooms; use Illuminate\Foundation\Http\FormRequest;
class PromotionConfirmRequest extends FormRequest {public function authorize():bool{return $this->user()->can('classrooms.promote');}public function rules():array{return ['batch_id'=>['required','exists:classroom_promotion_batches,id'],'confirmation'=>['accepted']];}}
