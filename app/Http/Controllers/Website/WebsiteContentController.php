<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\SaveContentRequest;
use App\Models\{LandingAchievement, LandingFacility, LandingNews, LandingProgram, LandingTestimonial};
use App\Services\Website\MediaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebsiteContentController extends Controller
{
    private const TYPES = [
        'program' => ['model' => LandingProgram::class, 'label' => 'Program unggulan', 'singular' => 'program'],
        'facility' => ['model' => LandingFacility::class, 'label' => 'Fasilitas', 'singular' => 'fasilitas'],
        'achievement' => ['model' => LandingAchievement::class, 'label' => 'Prestasi', 'singular' => 'prestasi'],
        'news' => ['model' => LandingNews::class, 'label' => 'Berita dan kegiatan', 'singular' => 'berita'],
        'testimonial' => ['model' => LandingTestimonial::class, 'label' => 'Testimoni', 'singular' => 'testimoni'],
    ];

    private function meta(string $type): array
    {
        return self::TYPES[$type] ?? abort(404);
    }

    public function index(string $type)
    {
        $meta = $this->meta($type);

        return view('website.admin.content-index', [
            'type' => $type,
            'meta' => $meta,
            'items' => $meta['model']::latest()->paginate(15),
        ]);
    }

    public function create(string $type)
    {
        return view('website.admin.content-form', ['type' => $type, 'meta' => $this->meta($type), 'item' => null]);
    }

    public function store(SaveContentRequest $request, string $type, MediaService $media)
    {
        $meta = $this->meta($type);
        $meta['model']::create($this->data($request, $type, $media));

        return redirect()->route('website.content.index', $type)->with('status', ucfirst($meta['singular']).' berhasil ditambahkan.');
    }

    public function edit(string $type, int $id)
    {
        $meta = $this->meta($type);

        return view('website.admin.content-form', ['type' => $type, 'meta' => $meta, 'item' => $meta['model']::findOrFail($id)]);
    }

    public function update(SaveContentRequest $request, string $type, int $id, MediaService $media)
    {
        $meta = $this->meta($type);
        $item = $meta['model']::findOrFail($id);
        $item->update($this->data($request, $type, $media, $item));

        return back()->with('status', ucfirst($meta['singular']).' berhasil diperbarui.');
    }

    public function destroy(string $type, int $id, MediaService $media)
    {
        $meta = $this->meta($type);
        $item = $meta['model']::findOrFail($id);
        $media->delete($item->image ?? $item->featured_image ?? $item->photo ?? null);
        $item->delete();

        return back()->with('status', ucfirst($meta['singular']).' berhasil dihapus.');
    }

    private function data(SaveContentRequest $request, string $type, MediaService $media, ?Model $item = null): array
    {
        $allowed = match ($type) {
            'program' => ['title', 'slug', 'icon', 'summary', 'description', 'sort_order', 'is_active', 'featured'],
            'facility' => ['name', 'slug', 'description', 'icon', 'sort_order', 'is_active'],
            'achievement' => ['title', 'category', 'description', 'year', 'date', 'sort_order', 'is_active', 'featured'],
            'news' => ['title', 'slug', 'category', 'excerpt', 'content', 'published_at', 'status', 'featured'],
            'testimonial' => ['name', 'relation', 'content', 'rating', 'sort_order', 'is_active'],
        };
        $input = $request->validated();
        if ($type === 'testimonial') {
            $input['content'] = $request->input('description');
        }
        $data = collect($input)->only($allowed)->all();
        if ($type !== 'news') {
            $data['is_active'] = $request->boolean('is_active');
        }
        if (in_array($type, ['program', 'achievement', 'news'])) {
            $data['featured'] = $request->boolean('featured');
        }
        if (in_array($type, ['program', 'facility', 'news'])) {
            $base = $data['title'] ?? $data['name'] ?? '';
            $data['slug'] = $data['slug'] ?: Str::slug($base).'-'.Str::lower(Str::random(5));
        }
        if ($type === 'news') {
            $data['author_id'] = $request->user()->id;
            if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
                $data['published_at'] = now();
            }
            $field = 'featured_image';
        } elseif ($type === 'testimonial') {
            $field = 'photo';
        } else {
            $field = 'image';
        }
        $data[$field] = $media->replace($request->file('image'), $item?->{$field}, $type);

        return $data;
    }
}
