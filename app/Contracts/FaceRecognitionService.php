<?php
declare(strict_types=1);
namespace App\Contracts;
use App\Models\Personnel;use Illuminate\Http\UploadedFile;
interface FaceRecognitionService{/** @return array{matched_personnel_id:int|null,confidence:float|null,faces:int,liveness_passed:bool|null} */public function verify(Personnel $expected,UploadedFile $snapshot,float $threshold):array;public function provider():string;public function livenessSupported():bool;}
