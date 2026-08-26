<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('website.content.manage') ?? false;
    }

    public function rules(): array
    {
        $type = $this->route('type');

        return [
            'title' => [Rule::requiredIf(in_array($type, ['program', 'achievement', 'news'])), 'nullable', 'string', 'max:255'],
            'name' => [Rule::requiredIf(in_array($type, ['facility', 'testimonial'])), 'nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'category' => [Rule::requiredIf($type === 'news'), 'nullable', 'in:Berita,Kegiatan,Prestasi,Pengumuman'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'description' => [Rule::requiredIf($type === 'testimonial'), 'nullable', 'string', 'max:10000'],
            'content' => [Rule::requiredIf($type === 'news'), 'nullable', 'string', 'max:50000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'icon' => ['nullable', 'string', 'max:50'],
            'relation' => ['nullable', 'string', 'max:255'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
            'date' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:draft,published'],
            'is_active' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'judul', 'name' => 'nama', 'category' => 'kategori', 'description' => 'isi testimoni', 'content' => 'isi berita'];
    }
}
