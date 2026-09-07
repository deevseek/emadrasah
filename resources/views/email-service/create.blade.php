<x-layouts.app title="Tulis Email" :breadcrumbs="['Pelayanan', 'Email', 'Tulis Email']">
    <div class="mx-auto max-w-5xl space-y-4">
        <x-ui.page-header title="Tulis Email" description="Susun dan kirim email resmi melalui konfigurasi email aplikasi." />

        <x-ui.card>
            <form method="post" action="{{ route('email-service.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Pengirim resmi</p>
                    <p class="mt-1 font-bold text-emerald-950">{{ config('mail.from.name') }}</p>
                    <p class="text-sm text-emerald-800">{{ config('mail.from.address') }}</p>
                    <p class="mt-2 text-xs text-emerald-700">Alamat pengirim dan Reply-To ditetapkan oleh konfigurasi Laravel dan tidak dapat diubah dari formulir.</p>
                </div>

                <div class="grid gap-4">
                    <label>Kepada <span class="text-rose-600">*</span>
                        <textarea name="to_addresses" rows="2" required placeholder="nama@example.com, penerima@example.org">{{ is_array(old('to_addresses')) ? implode(', ', old('to_addresses')) : old('to_addresses', '') }}</textarea>
                        <span class="mt-1 block text-xs font-normal text-slate-500">Pisahkan beberapa alamat dengan koma, titik koma, atau baris baru.</span>
                    </label>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label>CC
                            <textarea name="cc_addresses" rows="2" placeholder="cc@example.com">{{ is_array(old('cc_addresses')) ? implode(', ', old('cc_addresses')) : old('cc_addresses', '') }}</textarea>
                        </label>
                        <label>BCC
                            <textarea name="bcc_addresses" rows="2" placeholder="bcc@example.com">{{ is_array(old('bcc_addresses')) ? implode(', ', old('bcc_addresses')) : old('bcc_addresses', '') }}</textarea>
                        </label>
                    </div>
                    <x-ui.input name="subject" label="Subjek *" :value="old('subject')" maxlength="255" required />
                    <label>Isi Email <span class="text-rose-600">*</span>
                        <textarea name="body" rows="14" maxlength="100000" required placeholder="Tuliskan isi email resmi di sini...">{{ old('body') }}</textarea>
                        <span class="mt-1 block text-xs font-normal text-slate-500">Teks akan ditampilkan dengan aman sesuai pemisah baris yang Anda tulis.</span>
                    </label>
                    <label>Lampiran
                        <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                        <span class="mt-1 block text-xs font-normal text-slate-500">Maksimal 5 file, masing-masing 10 MB. Format: PDF, Word, Excel, JPG, atau PNG.</span>
                    </label>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-5 sm:flex-row sm:justify-between">
                    <x-ui.button :href="route('email-service.index')" variant="secondary"><x-ui.icon name="back" /> Kembali</x-ui.button>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <x-ui.button type="submit" name="action" value="draft" variant="outline">Simpan Draft</x-ui.button>
                        <x-ui.button type="submit" name="action" value="send">Kirim Email</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
