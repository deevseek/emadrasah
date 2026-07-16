<?php

declare(strict_types=1);

namespace App\Http\Controllers\Btaq;

use App\Http\Controllers\Controller;
use App\Http\Requests\Btaq\StoreBtaqLevelRequest;
use App\Http\Requests\Btaq\UpdateBtaqLevelRequest;
use App\Models\BtaqLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BtaqLevelController extends Controller
{
    public function index(Request $request): View { $levels = BtaqLevel::query()->when($request->q, fn ($q, $v) => $q->where('name', 'like', "%$v%"))->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('is_active', (bool) $request->status))->orderBy('sequence')->paginate(15); return view('btaq.levels.index', compact('levels')); }
    public function create(): View { return view('btaq.levels.form', ['level' => new BtaqLevel]); }
    public function store(StoreBtaqLevelRequest $request): RedirectResponse { BtaqLevel::create($request->validated()); activity('btaq')->event('btaq-level.created')->log('Level BTAQ dibuat'); return redirect()->route('btaq-levels.index')->with('status','Level BTAQ disimpan.'); }
    public function show(BtaqLevel $btaqLevel): View { return view('btaq.levels.show', ['level' => $btaqLevel]); }
    public function edit(BtaqLevel $btaqLevel): View { return view('btaq.levels.form', ['level' => $btaqLevel]); }
    public function update(UpdateBtaqLevelRequest $request, BtaqLevel $btaqLevel): RedirectResponse { $btaqLevel->update($request->validated()); activity('btaq')->performedOn($btaqLevel)->event('btaq-level.updated')->log('Level BTAQ diperbarui'); return redirect()->route('btaq-levels.show',$btaqLevel)->with('status','Level BTAQ diperbarui.'); }
    public function toggle(BtaqLevel $btaqLevel): RedirectResponse { $btaqLevel->update(['is_active'=>! $btaqLevel->is_active]); return back()->with('status','Status level diperbarui.'); }
}
