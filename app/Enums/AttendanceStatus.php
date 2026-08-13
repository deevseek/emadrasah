<?php
declare(strict_types=1);
namespace App\Enums;
enum AttendanceStatus:string { case Present='present'; case Sick='sick'; case Permitted='permitted'; case Absent='absent'; public function label():string{return match($this){self::Present=>'Hadir',self::Sick=>'Sakit',self::Permitted=>'Izin',self::Absent=>'Alpa'};} }
