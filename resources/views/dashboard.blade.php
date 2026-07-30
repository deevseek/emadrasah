<x-app-layout :title="$title">
<div class="space-y-6">
  <section class="rounded-3xl bg-emerald-950 p-6 text-white shadow-sm">
    <p class="text-sm text-emerald-100">Beranda e-Madrasah</p>
    <h2 class="mt-2 text-2xl font-black">Fondasi e-Madrasah</h2>
    <p class="mt-2 max-w-3xl text-sm text-emerald-50">Seluruh modul lama telah dibersihkan. Aplikasi siap dibangun ulang dengan alur kerja yang lebih sederhana dan terstruktur.</p>
  </section>

  <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" aria-label="Informasi fondasi">
    <x-ui.stat-card title="Status aplikasi" value="Siap dikembangkan" />
    <x-ui.stat-card title="Autentikasi" value="Aktif" />
    <x-ui.stat-card title="Hak akses" value="Aktif" />
    <x-ui.stat-card title="Modul terpasang" value="0" />
  </section>

  <section class="card">
    <div class="card-body">
      <h2 class="text-lg font-bold text-emerald-950">Area modul</h2>
      <x-ui.empty-state title="Belum ada modul yang dipasang.">
        Modul baru akan dibangun secara bertahap.
      </x-ui.empty-state>
    </div>
  </section>
</div>
</x-app-layout>
