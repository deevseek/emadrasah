<x-layouts.app :title="$title" :breadcrumbs="['Beranda','Data Personalia','Hubungkan Akun']">
    <x-ui.card>
        <h1 class="text-xl font-black text-emerald-950">Hubungkan Akun</h1>
        <p class="mt-2">Personalia: <strong>{{ $personnel->full_name }}</strong></p>
        <p>Akun saat ini: {{ $personnel->user?->name ?: 'Belum terhubung' }}</p>
        @if($suggestedRole)
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-slate-700">
                <strong>Role yang disarankan: {{ $suggestedRole->display_name }}</strong>
                <p class="mt-1">Saran berasal dari jabatan personalia dan tidak diterapkan secara otomatis.</p>
            </div>
        @endif
        @if($accounts->isEmpty())
            <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="font-semibold text-emerald-950">Belum ada akun yang dapat dihubungkan.</p>
                <p class="mt-1 text-sm text-slate-600">Buat akun pengguna terlebih dahulu. Akun Super Admin, akun Anda sendiri, dan akun yang sudah terhubung tidak ditampilkan demi keamanan.</p>
                @can('users.create')<a class="btn btn-primary mt-4" href="{{ route('users.create', ['name' => $personnel->full_name, 'email' => $personnel->email]) }}">Buat Akun Pengguna</a>@endcan
            </div>
        @else
        <form method="post" action="{{ route('personnel.account.update',$personnel) }}" class="mt-5" @if($suggestedRole && auth()->user()->can('users.assign-role')) onsubmit="return !this.apply_suggested_role.checked || confirm('Hubungkan akun dan ubah role akun menjadi {{ $suggestedRole->display_name }}?')" @endif>
            @csrf @method('PATCH')
            <label for="user_id" class="label">Akun pengguna</label>
            <select name="user_id" class="input" required>
                <option value="">Pilih akun yang belum terhubung</option>
                @foreach($accounts as $a)<option value="{{ $a->id }}" @selected(old('user_id')==$a->id)>{{ $a->name }} — {{ $a->email }} — {{ $a->display_role }}</option>@endforeach
            </select>
            @error('user_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
            @if($suggestedRole && auth()->user()->can('users.assign-role'))
                <label class="mt-4 flex items-start gap-2 rounded-xl bg-slate-50 p-3"><input type="checkbox" name="apply_suggested_role" value="1" @checked(old('apply_suggested_role'))><span>Sesuaikan role akun menjadi <strong>{{ $suggestedRole->display_name }}</strong>. Pilihan ini tidak dicentang secara default.</span></label>
            @endif
            <div class="mt-4 flex gap-2"><a class="btn btn-secondary" href="{{ route('personnel.show',$personnel) }}">Batal</a><button class="btn btn-primary">Hubungkan Akun</button></div>
        </form>
        @endif
        @if($personnel->user)<form method="post" action="{{ route('personnel.account.destroy',$personnel) }}" class="mt-4" onsubmit="return confirm('Lepaskan hubungan akun dari personalia ini?')">@csrf @method('DELETE')<button class="btn btn-danger">Lepaskan Akun</button></form>@endif
    </x-ui.card>
</x-layouts.app>
