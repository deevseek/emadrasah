<?php
declare(strict_types=1);
namespace App\Enums\Hrd;
enum LeaveType:string {case Sick='sick';case Permission='permission';case Annual='annual_leave';case Maternity='maternity_leave';case Family='family';case OfficialDuty='official_duty';case Other='other';public function label():string{return match($this){self::Sick=>'Sakit',self::Permission=>'Izin',self::Annual=>'Cuti Tahunan',self::Maternity=>'Cuti Melahirkan',self::Family=>'Keperluan Keluarga',self::OfficialDuty=>'Dinas Luar',self::Other=>'Lainnya'};} }
