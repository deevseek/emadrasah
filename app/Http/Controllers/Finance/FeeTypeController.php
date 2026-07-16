<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FeeTypeStoreRequest;
use App\Http\Requests\Finance\FeeTypeUpdateRequest;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\FeeType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class FeeTypeController extends Controller
{
    public function index(Request $request): View
    {
        $feeTypes = FeeType::query()
            ->with('revenueAccount')
            ->withCount(['invoices', 'discounts'])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->string('category')->toString(),
                fn ($query, string $category) => $query->where('category', $category),
            )
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('finance.fee-types.index', compact('feeTypes'));
    }

    public function create(): View
    {
        return $this->form(new FeeType);
    }

    public function store(FeeTypeStoreRequest $request): RedirectResponse
    {
        $feeType = FeeType::create($request->validated());

        activity('student-finance')
            ->performedOn($feeType)
            ->causedBy($request->user())
            ->event('fee-type.created')
            ->log('Jenis tagihan dibuat');

        return redirect()
            ->route('finance.fee-types.show', $feeType)
            ->with('status', 'Jenis tagihan disimpan.');
    }

    public function show(FeeType $feeType): View
    {
        $feeType->load('revenueAccount')->loadCount(['invoices', 'discounts']);

        return view('finance.fee-types.show', compact('feeType'));
    }

    public function edit(FeeType $feeType): View
    {
        return $this->form($feeType);
    }

    public function update(
        FeeTypeUpdateRequest $request,
        FeeType $feeType,
    ): RedirectResponse {
        $old = $feeType->toArray();
        $feeType->update($request->validated());

        activity('student-finance')
            ->performedOn($feeType)
            ->causedBy($request->user())
            ->withProperties([
                'old' => $old,
                'new' => $feeType->fresh()->toArray(),
            ])
            ->event('fee-type.updated')
            ->log('Jenis tagihan diperbarui');

        return redirect()
            ->route('finance.fee-types.show', $feeType)
            ->with('status', 'Jenis tagihan diperbarui.');
    }

    public function toggle(Request $request, FeeType $feeType): RedirectResponse
    {
        abort_unless($request->user()?->can('fee-types.manage'), 403);

        $feeType->update(['is_active' => ! $feeType->is_active]);

        activity('student-finance')
            ->performedOn($feeType)
            ->causedBy($request->user())
            ->event($feeType->is_active ? 'fee-type.activated' : 'fee-type.deactivated')
            ->log($feeType->is_active ? 'Jenis tagihan diaktifkan' : 'Jenis tagihan dinonaktifkan');

        return back()->with('status', 'Status jenis tagihan diperbarui.');
    }

    public function destroy(Request $request, FeeType $feeType): RedirectResponse
    {
        abort_unless($request->user()?->can('fee-types.manage'), 403);

        if ($feeType->invoices()->exists() || $feeType->discounts()->exists()) {
            throw ValidationException::withMessages([
                'fee_type' => 'Jenis tagihan sudah dipakai dan tidak dapat dihapus. Nonaktifkan sebagai gantinya.',
            ]);
        }

        activity('student-finance')
            ->performedOn($feeType)
            ->causedBy($request->user())
            ->event('fee-type.deleted')
            ->log('Jenis tagihan dihapus');

        $feeType->delete();

        return redirect()
            ->route('finance.fee-types.index')
            ->with('status', 'Jenis tagihan dihapus.');
    }

    private function form(FeeType $feeType): View
    {
        $revenueAccounts = ChartAccount::query()
            ->where('account_type', 'revenue')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('finance.fee-types.form', compact('feeType', 'revenueAccounts'));
    }
}
