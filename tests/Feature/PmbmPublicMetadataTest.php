<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SchoolProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PmbmPublicMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_pmb_preview_identifies_the_school_without_javascript(): void
    {
        SchoolProfile::create(['name' => 'MI Muslimat NU Demak', 'short_name' => 'MIM NU Demak']);

        $this->withHeader('User-Agent', 'WhatsApp/2.0')->get(route('pmbm.public.index'))
            ->assertOk()
            ->assertSee('<title>Penerimaan Murid Baru Madrasah - MI Muslimat NU Demak</title>', false)
            ->assertSee('<meta property="og:title" content="Penerimaan Murid Baru Madrasah - MI Muslimat NU Demak">', false)
            ->assertSee('<meta property="og:description" content="Informasi dan pendaftaran calon murid baru MI Muslimat NU Demak. Lihat persyaratan, jadwal, dan alur pendaftaran.">', false)
            ->assertDontSee('Sultan Fatah');
    }

    public function test_public_layout_default_description_uses_the_school_profile(): void
    {
        SchoolProfile::create(['name' => 'MI Muslimat NU Demak', 'short_name' => 'MIM NU Demak']);

        $this->get(route('public.news'))->assertOk()
            ->assertSee('<meta name="description" content="Website resmi MI Muslimat NU Demak">', false)
            ->assertSee('<meta property="og:description" content="Website resmi MI Muslimat NU Demak">', false)
            ->assertDontSee('Sultan Fatah');
    }
}
