<x-layouts.app title="Atur Wali Kelas">
    <div class="space-y-6">
        @if(session('success'))
            <p class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-emerald-800" role="status">{{ session('success') }}</p>
        @endif
        <x-ui.page-header title="Atur Wali Kelas" :description="$classroom->display_name"/>
        <form method="post" action="{{ route('classrooms.homeroom.update', $classroom) }}" class="space-y-5">
            @csrf @method('PATCH')
            <x-ui.card>
                <p>Wali kelas saat ini: <strong>{{ $classroom->homeroom_name }}</strong></p>
                <label class="mt-4 block">
                    <span class="label">Pilih wali kelas baru <x-ui.help-tip title="Wali kelas" content="Guru tidak dapat menjadi wali dua rombel aktif pada tahun ajaran yang sama. Gunakan Tukar Wali Kelas jika guru sudah menjadi wali rombel lain."/></span>
                    <select class="input" name="homeroom_personnel_id">
                        <option value="">Kosongkan wali kelas</option>
                        @foreach($personnel as $p)
                            <option value="{{ $p->id }}" @selected((string) old('homeroom_personnel_id', $classroom->homeroom_personnel_id) === (string) $p->id)>{{ $p->full_name }} — {{ $p->position }}</option>
                        @endforeach
                    </select>
                </label>
                @error('homeroom_personnel_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </x-ui.card>
            <button class="btn btn-primary" onclick="return confirm('Ganti wali kelas? Perubahan akan dicatat dalam riwayat aktivitas.')">Simpan Wali Kelas</button>
        </form>

        <form method="post" action="{{ route('classrooms.homeroom.swap', $classroom) }}" class="space-y-5" onsubmit="return confirm('Tukar wali kelas kedua rombel? Kedua penugasan akan berubah sekaligus dan dicatat dalam riwayat aktivitas.')">
            @csrf @method('PATCH')
            <x-ui.card>
                <h2 class="text-lg font-bold text-emerald-900">Tukar Wali Kelas Antarrombel</h2>
                @if($classroom->is_active && $classroom->homeroom_personnel_id && $swapClassrooms->isNotEmpty())
                    <p class="mt-2 text-sm text-slate-600">{{ $classroom->homeroom_name }} akan menjadi wali kelas rombel yang dipilih. Wali kelas rombel tersebut akan menjadi wali kelas {{ $classroom->display_name }}. Tidak perlu mengosongkan wali kelas terlebih dahulu.</p>
                    <label class="mt-4 block">
                        <span class="label">Rombel pasangan dan wali kelas saat ini</span>
                        <select class="input" name="target_classroom_id" required>
                            <option value="">Pilih rombel untuk ditukar</option>
                            @foreach($swapClassrooms as $room)
                                <option value="{{ $room->id }}" @selected((string) old('target_classroom_id') === (string) $room->id)>{{ $room->display_name }} — {{ $room->homeroom_name }}</option>
                            @endforeach
                        </select>
                    </label>
                    @error('target_classroom_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    <button class="btn btn-primary mt-4">Tukar Wali Kelas</button>
                @else
                    <p class="mt-2 text-sm text-slate-600">Pertukaran memerlukan dua rombel aktif yang masing-masing memiliki wali kelas pada tahun ajaran yang sama. Untuk rombel tanpa wali kelas, gunakan Simpan Wali Kelas.</p>
                @endif
            </x-ui.card>
        </form>
    </div>
</x-layouts.app>
