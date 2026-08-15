<?php
declare(strict_types=1);
namespace App\Enums\Hrd;
enum AttendanceStatus:string { case Present='hadir'; case Late='terlambat'; case Permission='izin'; case Sick='sakit'; case Leave='cuti'; case OfficialDuty='dinas_luar'; case Absent='alpha'; public function label():string{return match($this){self::Present=>'Hadir',self::Late=>'Terlambat',self::Permission=>'Izin',self::Sick=>'Sakit',self::Leave=>'Cuti',self::OfficialDuty=>'Dinas Luar',self::Absent=>'Alpa'};} }
