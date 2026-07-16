<?php

declare(strict_types=1);

namespace App\Http\Controllers\Btaq;

use App\Http\Controllers\Controller;
use App\Models\BtaqLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BtaqLevelController extends Controller
{
    public function index(Request $request): View
    {
        $levels = BtaqLevel::query()
            ->when(
                $request->q,
                fn ($query, $value) => $query->where('name', 'like', "%{$value}%")
            )
            ->when(
                $request->status !== null && $request->status !== '',
                fn ($query) => $query->where('is_active', (bool) $request->status)
            )
            ->orderBy('sequence')
            ->paginate(15);

        return view('btaq.levels.index', compact('levels'));
    }

    public function create(): View
    {
        return view('btaq.levels.form', [
            'level' => new BtaqLevel,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        BtaqLevel::create($request->validate([
            'code' => 'required|string|max:50|unique:btaq_levels,code',
            'name' => 'required|string',
            'sequence' => 'required|integer',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]));

        activity('btaq')
            ->event('btaq-level.created')
            ->log('Level BTAQ dibuat');

        return redirect()->route('btaq-levels.index')
            ->with('status', 'Level BTAQ disimpan.');
    }

    public function show(BtaqLevel $btaqLevel): View
    {
        return view('btaq.levels.show', [
            'level' => $btaqLevel,
        ]);
    }

    public function edit(BtaqLevel $btaqLevel): View
    {
        return view('btaq.levels.form', [
            'level' => $btaqLevel,
        ]);
    }

    public function update(Request $request, BtaqLevel $btaqLevel): RedirectResponse
    {
        $btaqLevel->update($request->validate([
            'code' => 'required|string|max:50|unique:btaq_levels,code,'.$btaqLevel->id,
            'name' => 'required|string',
            'sequence' => 'required|integer',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]));

        activity('btaq')
            ->performedOn($btaqLevel)
            ->event('btaq-level.updated')
            ->log('Level BTAQ diperbarui');

        return redirect()->route('btaq-levels.show', $btaqLevel)
            ->with('status', 'Level BTAQ diperbarui.');
    }

    public function toggle(BtaqLevel $btaqLevel): RedirectResponse
    {
        $btaqLevel->update([
            'is_active' => ! $btaqLevel->is_active,
        ]);

        return back()->with('status', 'Status level diperbarui.');
    }
}
