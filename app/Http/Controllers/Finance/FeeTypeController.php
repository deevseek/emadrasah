<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\FeeType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeTypeController extends Controller
{
    public function index(Request $request): View
    {
        $feeTypes = FeeType::query()
            ->when($request->search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
            ->when($request->category, fn ($query, $category) => $query->where('category', $category))
            ->latest()
            ->paginate(15);

        return view('finance.fee-types.index', compact('feeTypes'));
    }

    public function create(): View { return view('finance.fee-types.form', ['feeType' => new FeeType]); }
    public function show(FeeType $feeType): View { return view('finance.fee-types.show', compact('feeType')); }
    public function edit(FeeType $feeType): View { return view('finance.fee-types.form', compact('feeType')); }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $feeType = FeeType::create($data);
        activity('student-finance')->performedOn($feeType)->causedBy($request->user())->event('fee-type.created')->log('Jenis tagihan dibuat');

        return redirect()->route('finance.fee-types.show', $feeType)->with('status', 'Jenis tagihan disimpan.');
    }

    public function update(Request $request, FeeType $feeType): RedirectResponse
    {
        $old = $feeType->toArray();
        $feeType->update($this->validated($request, $feeType->id));
        activity('student-finance')->performedOn($feeType)->causedBy($request->user())->withProperties(['old' => $old, 'new' => $feeType->toArray()])->event('fee-type.updated')->log('Jenis tagihan diperbarui');

        return redirect()->route('finance.fee-types.show', $feeType)->with('status', 'Jenis tagihan diperbarui.');
    }

    public function toggle(FeeType $feeType): RedirectResponse
    {
        $feeType->update(['is_active' => ! $feeType->is_active]);

        return back()->with('status', 'Status jenis tagihan diperbarui.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:fee_types,code,'.($id ?? 'NULL').',id'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'billing_frequency' => ['required', 'string'],
            'is_mandatory' => ['boolean'],
            'is_active' => ['boolean'],
            'revenue_account_id' => ['nullable', 'exists:chart_accounts,id'],
        ]);
    }
}
