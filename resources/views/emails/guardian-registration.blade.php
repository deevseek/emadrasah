<p>Assalamu'alaikum Wr. Wb.</p>
<p>Yth. Bapak/Ibu <strong>{{ $guardian->name }}</strong>,</p>
<p>Pendaftaran akun Portal Orang Tua {{ $schoolProfile->display_name }} telah berhasil. Akun Anda telah terhubung dengan:</p>
<table>
    <tr><td>Nama anak</td><td>: {{ $student->full_name }}</td></tr>
    <tr><td>NISN</td><td>: {{ $student->nisn }}</td></tr>
    <tr><td>Username</td><td>: {{ $guardian->user->username }}</td></tr>
</table>
<p>Silakan masuk melalui <a href="{{ route('parent.login') }}">Portal Orang Tua</a> menggunakan email atau username Anda. Demi keamanan, kami tidak pernah mengirimkan password melalui email.</p>
<p>Setiap pembayaran SPP yang berhasil akan dikirimkan ke alamat email ini beserta tanda terima resmi dalam bentuk PDF.</p>
<p>Apabila Anda tidak melakukan pendaftaran ini, segera hubungi pihak madrasah.</p>
<p>Wassalamu'alaikum Wr. Wb.</p>
<p>{{ $schoolProfile->display_name }}</p>
