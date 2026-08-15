<?php
declare(strict_types=1);
namespace App\Services\Hrd;
use App\Contracts\FaceRecognitionService;use App\Models\Personnel;use Illuminate\Http\UploadedFile;use RuntimeException;
class UnavailableFaceRecognitionService implements FaceRecognitionService{public function verify(Personnel $expected,UploadedFile $snapshot,float $threshold):array{throw new RuntimeException('Provider pengenalan wajah belum dikonfigurasi.');}public function encode(UploadedFile $sample):array{throw new RuntimeException('Provider pengenalan wajah belum dikonfigurasi.');}public function health():array{return ['status'=>'unavailable','model_loaded'=>false];}public function provider():string{return'unavailable';}public function livenessSupported():bool{return false;}}
