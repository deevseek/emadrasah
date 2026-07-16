<?php

declare(strict_types=1);

namespace App\Http\Controllers\Btaq;

use App\Enums\BtaqMaterialCategory;
use App\Http\Controllers\Controller;
use App\Models\BtaqLevel;
use App\Models\BtaqMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BtaqMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $materials = BtaqMaterial::query()
            ->with('level')
            ->when(
                $request->q,
                fn ($query, $value) => $query->where('name', 'like', "%{$value}%")
            )
            ->when(
                $request->btaq_level_id,
                fn ($query, $value) => $query->where('btaq_level_id', $value)
            )
            ->when(
                $request->category,
                fn ($query, $value) => $query->where('category', $value)
            )
            ->paginate(15);

        return view('btaq.materials.index', [
            'materials' => $materials,
            'levels' => BtaqLevel::orderBy('sequence')->get(),
            'categories' => BtaqMaterialCategory::cases(),
        ]);
    }

    public function create(): View
    {
        return view('btaq.materials.form', [
            'material' => new BtaqMaterial,
            'levels' => BtaqLevel::where('is_active', true)->get(),
            'categories' => BtaqMaterialCategory::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        BtaqMaterial::create($request->validate([
            'btaq_level_id' => 'required|exists:btaq_levels,id',
            'code' => 'required|unique:btaq_materials,code',
            'name' => 'required',
            'category' => 'required',
            'sequence' => 'required|integer',
            'target_description' => 'nullable',
            'is_active' => 'boolean',
        ]));

        return redirect()->route('btaq-materials.index')
            ->with('status', 'Materi BTAQ disimpan.');
    }

    public function show(BtaqMaterial $btaqMaterial): View
    {
        return view('btaq.materials.show', [
            'material' => $btaqMaterial,
        ]);
    }

    public function edit(BtaqMaterial $btaqMaterial): View
    {
        return view('btaq.materials.form', [
            'material' => $btaqMaterial,
            'levels' => BtaqLevel::get(),
            'categories' => BtaqMaterialCategory::cases(),
        ]);
    }

    public function update(Request $request, BtaqMaterial $btaqMaterial): RedirectResponse
    {
        $btaqMaterial->update($request->validate([
            'btaq_level_id' => 'required|exists:btaq_levels,id',
            'code' => 'required|unique:btaq_materials,code,'.$btaqMaterial->id,
            'name' => 'required',
            'category' => 'required',
            'sequence' => 'required|integer',
            'target_description' => 'nullable',
            'is_active' => 'boolean',
        ]));

        return redirect()->route('btaq-materials.show', $btaqMaterial)
            ->with('status', 'Materi diperbarui.');
    }
}
