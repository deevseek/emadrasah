<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum FeeCategory: string { case Spp='spp'; case Registration='daftar_ulang'; case Activity='kegiatan'; case Book='buku'; case Uniform='seragam'; case Exam='ujian'; case Extracurricular='ekstrakurikuler'; case Donation='donasi'; case Other='lainnya'; }
