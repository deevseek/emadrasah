<x-app-layout title="Pengaturan Sistem">
@php
$labels=['general'=>'Umum','umum'=>'Umum','attendance'=>'Absensi','absensi'=>'Absensi','academic'=>'Akademik','akademik'=>'Akademik','security'=>'Keamanan','keamanan'=>'Keamanan','appearance'=>'Tampilan','tampilan'=>'Tampilan','system'=>'Sistem','sistem'=>'Sistem'];
$groups=$settings->getCollection()->groupBy('group');
$name=function($key){return str($key)->replace(['_','.'],' ')->headline()->replace('Enable','Aktifkan')->replace('Default','Bawaan')->replace('School','Madrasah');};
@endphp
<div class="space-y-6"><div><h2 class="text-2xl font-black text-emerald-950">Pengaturan Sistem</h2><p class="text-sm text-slate-500">Kelola konfigurasi aplikasi dalam kelompok yang mudah dipahami.</p></div>
@foreach($groups as $group=>$items)<div class="card"><div class="card-body"><div class="mb-5 flex items-center justify-between"><div><h3 class="text-lg font-bold text-emerald-950">{{ $labels[$group] ?? str($group)->headline() }}</h3><p class="text-sm text-slate-500">{{ $items->count() }} pengaturan tersedia.</p></div></div><div class="space-y-4">
@foreach($items as $setting)<form method="post" action="{{ route('settings.update',$setting) }}" class="grid gap-3 rounded-2xl border border-slate-100 bg-slate-50 p-4 md:grid-cols-[1fr_2fr_auto] md:items-end">@csrf @method('put')<div><label>{{ $name($setting->key) }}</label><p class="mt-1 text-xs text-slate-400">Key teknis: <code>{{ $setting->key }}</code></p></div><div>@if($setting->type==='boolean')<select name="value"><option value="1" @selected($setting->value==='1'||$setting->value===true)>Aktif</option><option value="0" @selected($setting->value==='0'||$setting->value===false)>Nonaktif</option></select>@elseif($setting->type==='integer')<input type="number" name="value" value="{{ old('value',$setting->value) }}">@elseif($setting->type==='time')<input type="time" name="value" value="{{ old('value',$setting->value) }}">@elseif($setting->type==='textarea')<textarea name="value" rows="2">{{ old('value',$setting->value) }}</textarea>@else<input name="value" value="{{ old('value',$setting->value) }}">@endif</div><button class="btn btn-primary">Simpan</button></form>@endforeach
</div></div></div>@endforeach
<div>{{ $settings->links() }}</div></div>
</x-app-layout>
