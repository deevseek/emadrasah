<?php
declare(strict_types=1);
namespace App\Http\Requests\Settings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateGeneralSettingRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('application-settings.update')===true;}
    protected function prepareForValidation():void{$data=collect($this->except(['_token','_method']))->map(fn($value)=>is_string($value)?trim($value):$value)->all();foreach(['maintenance_mode','attendance_rfid_enabled','rfid_writer_enabled'] as$key)$data[$key]=$this->boolean($key);if(isset($data['app_email']))$data['app_email']=strtolower($data['app_email']);$this->merge($data);}
    public function rules():array{$image=['nullable','file','mimes:png,jpg,jpeg,webp','max:2048'];return [
        'app_name'=>['required','string','max:100'],'app_short_name'=>['required','string','max:50'],'app_description'=>['nullable','string','max:255'],'institution_name'=>['required','string','max:150'],'app_email'=>['nullable','email','max:255'],'app_phone'=>['nullable','string','max:30'],'app_website'=>['nullable','url','max:255'],
        'primary_logo'=>$image,'login_logo'=>$image,'print_logo'=>$image,'favicon'=>['nullable','file','mimes:png,ico','max:512'],
        'primary_color'=>['required','regex:/^#[0-9A-Fa-f]{6}$/'],'default_theme'=>['required',Rule::in(['light'])],'sidebar_mode'=>['required',Rule::in(['expanded','compact'])],
        'default_language'=>['required',Rule::in(['id'])],'timezone'=>['required','timezone'],'date_format'=>['required',Rule::in(['DD/MM/YYYY','DD-MM-YYYY','YYYY-MM-DD'])],'time_format'=>['required',Rule::in(['24','12'])],'first_day_of_week'=>['required',Rule::in(['monday','sunday'])],
        'attendance_rfid_enabled'=>['required','boolean'],'rfid_writer_enabled'=>['required','boolean'],'maintenance_mode'=>['required','boolean'],'maintenance_message'=>['required','string','max:500'],'pagination_size'=>['required','integer',Rule::in([10,20,25,50,100])],
    ];}

    public function messages(): array
    {
        return [
            'primary_logo.file' => 'Logo Utama gagal diunggah. Silakan pilih kembali berkas gambar yang valid.',
            'primary_logo.mimes' => 'Logo Utama harus berformat PNG, JPG, JPEG, atau WEBP.',
            'primary_logo.max' => 'Ukuran Logo Utama maksimal 2 MB.',
            'login_logo.file' => 'Logo Login gagal diunggah. Silakan pilih kembali berkas gambar yang valid.',
            'login_logo.mimes' => 'Logo Login harus berformat PNG, JPG, JPEG, atau WEBP.',
            'login_logo.max' => 'Ukuran Logo Login maksimal 2 MB.',
            'print_logo.file' => 'Logo Cetak gagal diunggah. Silakan pilih kembali berkas gambar yang valid.',
            'print_logo.mimes' => 'Logo Cetak harus berformat PNG, JPG, JPEG, atau WEBP.',
            'print_logo.max' => 'Ukuran Logo Cetak maksimal 2 MB.',
            'favicon.file' => 'Favicon gagal diunggah. Silakan pilih kembali berkas gambar yang valid.',
            'favicon.mimes' => 'Favicon harus berformat PNG atau ICO.',
            'favicon.max' => 'Ukuran Favicon maksimal 512 KB.',
        ];
    }
}
