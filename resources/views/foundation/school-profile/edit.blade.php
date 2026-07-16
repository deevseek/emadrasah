<x-app-layout title="Profil Madrasah">
<form method="post" action="{{ route('school-profile.update') }}" enctype="multipart/form-data" class="space-y-6">
@csrf @method('put')
@php($sections = [
 'Identitas Madrasah' => [['school_name','Nama madrasah','text',true],['foundation_name','Nama yayasan','text',false],['nsm','NSM','text',false],['npsn','NPSN','text',false]],
 'Alamat' => [['address','Alamat lengkap','textarea',false],['village','Desa/Kelurahan','text',false],['district','Kecamatan','text',false],['city','Kabupaten/Kota','text',false],['province','Provinsi','text',false],['postal_code','Kode Pos','text',false]],
 'Kontak' => [['phone','Nomor telepon/WhatsApp','text',false],['email','Email','email',false],['website','Website','url',false]],
 'Kepala Madrasah' => [['principal_name','Nama kepala madrasah','text',false],['timezone','Timezone','text',true]],
])
@foreach($sections as $heading => $fields)
<div class="card"><div class="card-body"><h2 class="text-lg font-bold text-emerald-950">{{ $heading }}</h2><div class="mt-5 grid gap-4 md:grid-cols-2">
@foreach($fields as $field)<div @class(['md:col-span-2' => $field[2] === 'textarea'])><label>{{ $field[1] }} @if($field[3])<span class="text-rose-600">*</span>@endif</label>@if($field[2] === 'textarea')<textarea name="{{ $field[0] }}" rows="3">{{ old($field[0], $profile->{$field[0]}) }}</textarea>@else<input type="{{ $field[2] }}" name="{{ $field[0] }}" value="{{ old($field[0], $profile->{$field[0]}) }}" @required($field[3])>@endif @error($field[0])<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>@endforeach
</div></div></div>
@endforeach
<div class="card"><div class="card-body"><h2 class="text-lg font-bold text-emerald-950">Logo, Tanda Tangan, dan Stempel</h2><div class="mt-5 grid gap-6 md:grid-cols-3">
@foreach([['logo','logo_path','Logo madrasah'],['principal_signature','principal_signature_path','Tanda tangan kepala madrasah'],['stamp','stamp_path','Stempel madrasah']] as $image)
<div><div class="grid h-40 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">@if($profile->{$image[1]})<img src="{{ Storage::url($profile->{$image[1]}) }}" class="h-full w-full object-contain" alt="{{ $image[2] }} saat ini">@else<span class="text-center text-sm text-slate-500">Belum ada {{ strtolower($image[2]) }}</span>@endif<img id="{{ $image[0] }}Preview" class="hidden h-full w-full object-contain" alt="Preview {{ $image[2] }}"></div><label class="mt-3 block">{{ $image[2] }}</label><input type="file" name="{{ $image[0] }}" accept="image/*" onchange="previewImage(event,'{{ $image[0] }}Preview')"><p class="mt-1 text-xs text-slate-500">Format gambar, maksimal 2 MB.</p>@error($image[0])<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror</div>
@endforeach
</div></div></div>
<div class="flex flex-wrap justify-end gap-2"><a href="{{ route('dashboard') }}" class="btn btn-secondary">Kembali</a>@can('school-profile.update')<button class="btn btn-primary">Simpan Perubahan</button>@endcan</div>
</form>
<script>function previewImage(event,id){const preview=document.getElementById(id);preview.src=URL.createObjectURL(event.target.files[0]);preview.classList.remove('hidden');}</script>
</x-app-layout>
