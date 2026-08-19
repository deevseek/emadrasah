<div><h2 class="text-lg font-bold text-emerald-950">INTEGRASI BANK BRI</h2><p class="text-sm text-slate-500">Perubahan di halaman ini otomatis disinkronkan ke konfigurasi server. Anda tidak perlu mengedit file .env secara manual.</p></div>
<div class="grid gap-3 sm:grid-cols-4">
 @foreach(['Status Integrasi'=>$bri->enabled()?'Aktif':'Nonaktif','Environment'=>ucfirst($bri->environment()),'Connection'=>$briSetting?->last_connection_success?'Terhubung':'Belum terverifikasi','ENV Sync'=>$briEnvWritable?'Aktif':'Tidak dapat menulis'] as $label=>$value)<div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">{{ $label }}</p><p class="font-semibold text-emerald-950">{{ $value }}</p></div>@endforeach
</div>
<form method="post" enctype="multipart/form-data" class="space-y-5">@csrf @method('PUT')
<div class="grid gap-4 md:grid-cols-2">
 <label class="flex justify-between rounded-xl border p-4"><span>Status Integrasi</span><span><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" @checked(old('enabled',$bri->enabled()))></span></label>
 <div><label>Environment</label><select name="environment"><option value="sandbox" @selected(old('environment',$bri->environment())==='sandbox')>Sandbox</option><option value="production" @selected(old('environment',$bri->environment())==='production')>Production</option></select></div>
</div>
<div class="border-t pt-5"><h3 class="font-bold text-emerald-950">Koneksi SNAP BI</h3><div class="mt-3 grid gap-4 md:grid-cols-2">
 <div class="md:col-span-2"><label>Base URL</label><input type="url" name="base_url" value="{{ old('base_url',$bri->baseUrl()) }}"></div>
 @foreach(['client_id'=>'Client ID / X-CLIENT-KEY','partner_id'=>'Partner ID / X-PARTNER-ID','channel_id'=>'Channel ID'] as $name=>$label)<div><label>{{ $label }}</label><input name="{{ $name }}" value="{{ old($name,$briSetting?->{$name}) }}"></div>@endforeach
 <div><label>Client Secret Baru</label><input type="password" name="client_secret" autocomplete="new-password"><p class="text-xs text-slate-500">Kosongkan jika tidak ingin mengganti secret. Status: {{ $briSetting?->client_secret?'Sudah dikonfigurasi':'Belum dikonfigurasi' }}</p></div>
 <div><label>Rekening Terdaftar Baru</label><input type="password" name="registered_account_number" autocomplete="new-password"><p class="text-xs text-slate-500">{{ $briSetting?->registered_account_number ? 'Status: ********'.substr($briSetting->registered_account_number,-4) : 'Belum dikonfigurasi' }}</p></div>
 <div><label>Private Key (PEM)</label><input type="file" name="private_key" accept=".pem"><p class="text-xs">{{ $bri->hasPrivateKey()?'Sudah tersedia':'Belum tersedia' }}</p></div><div><label>Public Key (PEM)</label><input type="file" name="public_key" accept=".pem"><p class="text-xs">{{ $bri->hasPublicKey()?'Sudah tersedia':'Belum tersedia' }}</p></div>
 <div><label>Toleransi Timestamp (detik)</label><input type="number" name="timestamp_tolerance" value="{{ old('timestamp_tolerance',$briSetting?->timestamp_tolerance??300) }}"></div><div><label>Timeout (detik)</label><input type="number" name="timeout" value="{{ old('timeout',$briSetting?->timeout??20) }}"></div>
</div></div>
<div class="border-t pt-5"><h3 class="font-bold text-emerald-950">BRIVA</h3><div class="mt-3 grid gap-4 md:grid-cols-2">
 <label><input type="hidden" name="briva_enabled" value="0"><input type="checkbox" name="briva_enabled" value="1" @checked(old('briva_enabled',$bri->brivaEnabled()))> Aktifkan BRIVA</label>
 <div><label>Mode VA</label><select name="briva_mode"><option value="per_student" @selected(old('briva_mode',$briSetting?->briva_mode??'per_student')==='per_student')>per_student</option><option value="per_invoice" @selected(old('briva_mode',$briSetting?->briva_mode)==='per_invoice')>per_invoice</option></select></div>
 @foreach(['partner_service_id'=>'Partner Service ID','institution_code'=>'Institution Code','customer_number_prefix'=>'Customer Prefix'] as $name=>$label)<div><label>{{ $label }}</label><input name="{{ $name }}" value="{{ old($name,$briSetting?->{$name}) }}"></div>@endforeach
</div></div>
<div class="border-t pt-5"><h3 class="font-bold text-emerald-950">QRIS</h3><div class="mt-3 grid gap-4 md:grid-cols-2"><label><input type="hidden" name="qris_enabled" value="0"><input type="checkbox" name="qris_enabled" value="1" @checked(old('qris_enabled',$bri->qrisEnabled()))> Aktifkan QRIS</label>
 @foreach(['merchant_id'=>'Merchant ID','terminal_id'=>'Terminal ID','qris_service_code'=>'Service Code QRIS','qris_notification_success_code'=>'Kode Sukses Notifikasi'] as $name=>$label)<div><label>{{ $label }}</label><input name="{{ $name }}" value="{{ old($name,$briSetting?->{$name}) }}"></div>@endforeach
</div></div>
<div class="border-t pt-5"><h3 class="font-bold text-emerald-950">PAYROLL</h3><div class="mt-3 grid gap-4 md:grid-cols-2"><label><input type="hidden" name="payroll_enabled" value="0"><input type="checkbox" name="payroll_enabled" value="1" @checked(old('payroll_enabled',$bri->payrollEnabled()))> Aktifkan Payroll</label>
 <div><label>Source Account Baru</label><input type="password" name="source_account" autocomplete="new-password"><p class="text-xs">{{ $briSetting?->source_account ? 'Status: ********'.substr($briSetting->source_account,-4) : 'Belum dikonfigurasi' }}</p></div><input type="hidden" name="payroll_method" value="{{ old('payroll_method',$briSetting?->payroll_method??'internal_bri') }}">
 @foreach(['intrabank_service_code'=>'Service Code Intrabank','interbank_service_code'=>'Service Code Interbank','status_inquiry_service_code'=>'Service Code Status Inquiry'] as $name=>$label)<div><label>{{ $label }}</label><input name="{{ $name }}" value="{{ old($name,$briSetting?->{$name}) }}"></div>@endforeach
</div></div>
<div class="border-t pt-5"><h3 class="font-bold text-emerald-950">Endpoint & Fitur Lain</h3><div class="mt-3 grid gap-4 md:grid-cols-2">
 @foreach(['path_bank_statement'=>'Path Bank Statement','path_qris_generate'=>'Path QRIS Generate','path_transaction_status'=>'Path Transaction Status','path_intrabank_transfer'=>'Path Intrabank Transfer','path_interbank_transfer'=>'Path Interbank Transfer'] as $name=>$label)<div><label>{{ $label }}</label><input name="{{ $name }}" value="{{ old($name,$briSetting?->{$name}) }}"></div>@endforeach
 <label><input type="hidden" name="direct_debit_enabled" value="0"><input type="checkbox" name="direct_debit_enabled" value="1" @checked(old('direct_debit_enabled',$briSetting?->direct_debit_enabled??false))> Aktifkan Direct Debit</label>
</div></div>
<div class="flex gap-2"><button type="submit" formaction="{{ route('application-settings.bri.update') }}" class="btn btn-primary">Simpan Pengaturan BRI</button><button type="submit" formmethod="POST" formaction="{{ route('application-settings.bri.test') }}" name="_method" value="POST" class="btn btn-secondary">Tes Koneksi</button></div>
</form>
