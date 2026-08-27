<?php

namespace Tests\Feature\Website;

use App\Models\{LandingNews, LandingPageSetting, LandingProgram, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_is_public_and_has_responsive_navigation(): void
    {
        LandingPageSetting::create(['key' => 'landing_enabled', 'value' => '1', 'group' => 'publication']);
        LandingPageSetting::create(['key' => 'hero_title', 'value' => 'Hero Aktif', 'group' => 'hero']);
        $this->get('/')->assertOk()->assertSee('Hero Aktif')->assertSee('mobile-nav');
    }

    public function test_public_layout_component_can_be_rendered(): void
    {
        $this->blade('<x-layouts.public title="Halaman Publik">Konten publik</x-layouts.public>')->assertSee('<title>Halaman Publik</title>', false)->assertSee('Konten publik');
    }

    public function test_only_active_program_is_visible(): void
    {
        LandingProgram::create(['title' => 'Aktif', 'slug' => 'aktif', 'is_active' => true]);
        LandingProgram::create(['title' => 'Rahasia', 'slug' => 'rahasia', 'is_active' => false]);
        $this->get('/')->assertSee('Aktif')->assertDontSee('Rahasia');
    }

    public function test_draft_news_is_not_public_but_published_news_is(): void
    {
        LandingNews::create(['title' => 'Draft Rahasia', 'slug' => 'draft', 'category' => 'Berita', 'content' => 'x', 'status' => 'draft']);
        LandingNews::create(['title' => 'Berita Terbit', 'slug' => 'terbit', 'category' => 'Berita', 'content' => 'x', 'status' => 'published', 'published_at' => now()]);
        $this->get('/berita')->assertSee('Berita Terbit')->assertDontSee('Draft Rahasia');
        $this->get('/berita/terbit')->assertOk();
        $this->get('/berita/draft')->assertNotFound();
    }

    public function test_guest_cannot_manage_website(): void
    {
        $this->get('/website')->assertRedirect('/login');
    }

    public function test_seo_meta_is_rendered(): void
    {
        LandingPageSetting::create(['key' => 'meta_description', 'value' => 'Deskripsi SEO sekolah', 'group' => 'seo']);
        $this->get('/')->assertSee('Deskripsi SEO sekolah');
    }

    public function test_operator_sees_a_simple_form_with_only_relevant_news_fields(): void
    {
        $operator = $this->operator(['website.content.manage']);

        $this->actingAs($operator)->get(route('website.content.create', 'news'))
            ->assertOk()
            ->assertSee('Tambah berita')
            ->assertSee('Isi berita')
            ->assertSee('Simpan sebagai draf')
            ->assertDontSee('Nama pemberi testimoni')
            ->assertDontSee('Rating 1-5');
    }

    public function test_required_content_fields_are_validated_before_saving(): void
    {
        $operator = $this->operator(['website.content.manage']);

        $this->actingAs($operator)->post(route('website.content.store', 'news'), ['status' => 'draft'])
            ->assertSessionHasErrors(['title', 'category', 'content']);
    }

    public function test_published_news_receives_a_publication_time_automatically(): void
    {
        $operator = $this->operator(['website.content.manage']);

        $this->actingAs($operator)->post(route('website.content.store', 'news'), [
            'title' => 'Kegiatan Baru',
            'category' => 'Kegiatan',
            'content' => 'Isi kegiatan madrasah.',
            'status' => 'published',
        ])->assertRedirect(route('website.content.index', 'news'));

        $this->assertNotNull(LandingNews::firstOrFail()->published_at);
    }

    public function test_news_without_a_slug_receives_an_automatic_slug(): void
    {
        $operator = $this->operator(['website.content.manage']);

        $this->actingAs($operator)->post(route('website.content.store', 'news'), [
            'title' => 'Kegiatan Tanpa Slug',
            'category' => 'Kegiatan',
            'content' => 'Isi kegiatan madrasah.',
            'status' => 'draft',
        ])->assertRedirect(route('website.content.index', 'news'));

        $news = LandingNews::firstOrFail();

        $this->assertMatchesRegularExpression('/^kegiatan-tanpa-slug-[a-z0-9]{5}$/', $news->slug);
    }

    private function operator(array $permissions): User
    {
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission));
        }

        return $user;
    }
}
