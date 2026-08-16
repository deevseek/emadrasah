<?php
namespace App\Services\Website; use Illuminate\Http\UploadedFile;use Illuminate\Support\Facades\Storage;
class MediaService {public function replace(?UploadedFile $file,?string $old,string $folder):?string{if(!$file)return $old;$path=$file->store("landing-page/$folder",'public');if($old&&str_starts_with($old,'landing-page/'))Storage::disk('public')->delete($old);return $path;}public function delete(?string $path):void{if($path&&str_starts_with($path,'landing-page/'))Storage::disk('public')->delete($path);}}
