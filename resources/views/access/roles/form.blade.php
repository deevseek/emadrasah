<x-layouts.app :title="$editing ? 'Edit Hak Akses' : 'Tambah Role'" :breadcrumbs="['Beranda', 'Pengaturan', 'Role & Hak Akses', $editing ? 'Edit' : 'Tambah']">
<div class="mx-auto max-w-6xl space-y-6" x-data="permissionEditor()">
<x-ui.page-header :title="$editing ? 'Edit Hak Akses '.$role->display_name : 'Tambah Role'" description="Hak akses dikelompokkan berdasarkan modul agar mudah dipahami." />
<form method="post" action="{{ $editing ? route('roles.update', $role) : route('roles.store') }}" class="space-y-6">
@csrf @if($editing) @method('PUT') @endif
<x-ui.card><div class="grid gap-4 md:grid-cols-2"><x-ui.input name="display_name" label="Nama Role" :value="$role->display_name" :disabled="$editing && $role->is_system" required />@if($editing && $role->is_system)<input type="hidden" name="display_name" value="{{ $role->display_name }}">@endif<x-ui.textarea name="description" label="Deskripsi">{{ old('description', $role->description) }}</x-ui.textarea></div></x-ui.card>
<div class="flex flex-col gap-3 rounded-xl bg-emerald-950 p-4 text-white md:flex-row md:items-center md:justify-between"><label class="flex-1"><span class="sr-only">Cari hak akses</span><input x-model="query" class="input text-slate-900" placeholder="Cari hak akses atau modul…"></label><strong><span x-text="count"></span> hak akses dipilih</strong></div>
@foreach(collect($groups)->groupBy(fn ($group) => $group['category']) as $category => $modules)
<section class="space-y-3"><h2 class="text-sm font-black tracking-widest text-emerald-900">{{ $category }}</h2><div class="grid gap-4 md:grid-cols-2">
@foreach($modules as $key => $group)
<div class="card card-body" x-show="matches(@js(strtolower($group['label'].' '.implode(' ', $group['permissions']))))" data-module="{{ $key }}"><div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-3"><h3 class="font-bold text-emerald-950">{{ $group['label'] }}</h3><label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" @change="toggleModule($el)"> Pilih Semua</label></div><div class="mt-4 space-y-3">@foreach($group['permissions'] as $permission => $label)<label class="flex items-start gap-3 font-normal"><input class="permission-checkbox mt-1 h-4 w-4" type="checkbox" name="permissions[]" value="{{ $permission }}" @change="refresh()" @checked(in_array($permission, old('permissions', $selected)))><span>{{ $label }}</span></label>@endforeach</div></div>
@endforeach
</div></section>
@endforeach
<div class="flex justify-end gap-2"><x-ui.button variant="secondary" href="{{ route('roles.index') }}">Batal</x-ui.button><x-ui.button><x-ui.icon name="save"/>Simpan Hak Akses</x-ui.button></div>
</form></div>
<script>function permissionEditor(){return{query:'',count:0,init(){this.refresh()},matches(text){return text.includes(this.query.toLowerCase())},refresh(){this.count=document.querySelectorAll('.permission-checkbox:checked').length},toggleModule(box){box.closest('[data-module]').querySelectorAll('.permission-checkbox').forEach(item=>item.checked=box.checked);this.refresh()}}}</script>
</x-layouts.app>
