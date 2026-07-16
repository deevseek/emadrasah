<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CashAccountRequest;
use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CashAccountController extends Controller
{
    public function index(Request $request): View
    {
        $items = CashAccount::query()
            ->with('chartAccount')
            ->withCount('transactionLines')
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('account_number', 'like', "%{$search}%")
                        ->orWhere('bank_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('finance.generic.index', [
            'title' => 'Kas dan Rekening',
            'description' => 'Kelola sumber dan tujuan arus kas yang dipakai jurnal keuangan.',
            'items' => $items,
            'headers' => ['Nama', 'Akun Jurnal', 'Bank', 'Nomor Rekening', 'Saldo', 'Transaksi', 'Status'],
            'rowBuilder' => static fn (CashAccount $cash): array => [
                $cash->name,
                ($cash->chartAccount?->code ?? '-').' — '.($cash->chartAccount?->name ?? '-'),
                $cash->bank_name ?? '-',
                $cash->account_number ?? '-',
                'Rp '.number_format((float) $cash->current_balance, 0, ',', '.'),
                (string) $cash->transaction_lines_count,
                [
                    'value' => $cash->is_active ? 'Aktif' : 'Nonaktif',
                    'variant' => $cash->is_active ? 'success' : 'muted',
                ],
            ],
            'createRoute' => route('finance.cash-accounts.create'),
            'createLabel' => 'Tambah Rekening',
            'showRouteName' => 'finance.cash-accounts.show',
            'editRouteName' => 'finance.cash-accounts.edit',
            'toggleRouteName' => 'finance.cash-accounts.toggle',
            'destroyRouteName' => 'finance.cash-accounts.destroy',
            'viewPermission' => 'finance-accounts.view',
            'managePermission' => 'finance-accounts.manage',
            'canDelete' => static fn (CashAccount $cash): bool => $cash->transaction_lines_count === 0,
            'searchPlaceholder' => 'Nama, bank, atau nomor rekening',
            'emptyTitle' => 'Belum ada rekening kas',
        ]);
    }

    public function create(): View
    {
        return $this->form(new CashAccount);
    }

    public function store(CashAccountRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureCashChartAccount((int) $data['chart_account_id']);
        $cashAccount = CashAccount::create([
            ...$data,
            'current_balance' => $data['opening_balance'],
        ]);

        activity('finance')
            ->performedOn($cashAccount)
            ->causedBy($request->user())
            ->event('cash-account.created')
            ->log('Rekening kas dibuat');

        return redirect()
            ->route('finance.cash-accounts.show', $cashAccount)
            ->with('status', 'Rekening kas berhasil dibuat.');
    }

    public function show(CashAccount $cashAccount): View
    {
        $cashAccount->load('chartAccount')->loadCount('transactionLines');

        return view('finance.generic.show', [
            'title' => 'Detail Kas dan Rekening',
            'description' => $cashAccount->name,
            'details' => [
                'Akun Jurnal' => ($cashAccount->chartAccount?->code ?? '-').' — '.($cashAccount->chartAccount?->name ?? '-'),
                'Nama Rekening' => $cashAccount->name,
                'Bank' => $cashAccount->bank_name ?? '-',
                'Nomor Rekening' => $cashAccount->account_number ?? '-',
                'Saldo Awal' => 'Rp '.number_format((float) $cashAccount->opening_balance, 0, ',', '.'),
                'Saldo Saat Ini' => 'Rp '.number_format((float) $cashAccount->current_balance, 0, ',', '.'),
                'Jumlah Baris Jurnal' => $cashAccount->transaction_lines_count,
            ],
            'status' => [
                'label' => $cashAccount->is_active ? 'Aktif' : 'Nonaktif',
                'variant' => $cashAccount->is_active ? 'success' : 'muted',
            ],
            'indexRoute' => route('finance.cash-accounts.index'),
            'editRoute' => route('finance.cash-accounts.edit', $cashAccount),
            'toggleRoute' => route('finance.cash-accounts.toggle', $cashAccount),
            'destroyRoute' => route('finance.cash-accounts.destroy', $cashAccount),
            'isActive' => $cashAccount->is_active,
            'canDelete' => $cashAccount->transaction_lines_count === 0,
            'viewPermission' => 'finance-accounts.view',
            'managePermission' => 'finance-accounts.manage',
            'deleteConfirmation' => 'Hapus rekening kas ini?',
        ]);
    }

    public function edit(CashAccount $cashAccount): View
    {
        return $this->form($cashAccount->loadCount('transactionLines'));
    }

    public function update(
        CashAccountRequest $request,
        CashAccount $cashAccount,
    ): RedirectResponse {
        $cashAccount->loadCount('transactionLines');
        $data = $request->validated();
        $this->ensureCashChartAccount((int) $data['chart_account_id']);

        if (
            $cashAccount->transaction_lines_count > 0
            && bccomp((string) $data['opening_balance'], (string) $cashAccount->opening_balance, 2) !== 0
        ) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Saldo awal tidak dapat diubah setelah rekening dipakai jurnal.',
            ]);
        }

        if ($cashAccount->transaction_lines_count === 0) {
            $data['current_balance'] = $data['opening_balance'];
        } else {
            unset($data['opening_balance']);
        }

        $cashAccount->update($data);

        activity('finance')
            ->performedOn($cashAccount)
            ->causedBy($request->user())
            ->event('cash-account.updated')
            ->log('Rekening kas diperbarui');

        return redirect()
            ->route('finance.cash-accounts.show', $cashAccount)
            ->with('status', 'Rekening kas berhasil diperbarui.');
    }

    public function toggle(Request $request, CashAccount $cashAccount): RedirectResponse
    {
        abort_unless($request->user()?->can('finance-accounts.manage'), 403);

        if (! $cashAccount->is_active && ! $cashAccount->chartAccount?->is_active) {
            throw ValidationException::withMessages([
                'cash_account' => 'Akun jurnal induk harus diaktifkan terlebih dahulu.',
            ]);
        }

        $cashAccount->update(['is_active' => ! $cashAccount->is_active]);

        activity('finance')
            ->performedOn($cashAccount)
            ->causedBy($request->user())
            ->event($cashAccount->is_active ? 'cash-account.activated' : 'cash-account.deactivated')
            ->log($cashAccount->is_active ? 'Rekening kas diaktifkan' : 'Rekening kas dinonaktifkan');

        return back()->with('status', 'Status rekening kas diperbarui.');
    }

    public function destroy(Request $request, CashAccount $cashAccount): RedirectResponse
    {
        abort_unless($request->user()?->can('finance-accounts.manage'), 403);

        if ($cashAccount->transactionLines()->exists()) {
            throw ValidationException::withMessages([
                'cash_account' => 'Rekening sudah dipakai jurnal dan tidak dapat dihapus. Nonaktifkan sebagai gantinya.',
            ]);
        }

        activity('finance')
            ->performedOn($cashAccount)
            ->causedBy($request->user())
            ->event('cash-account.deleted')
            ->log('Rekening kas dihapus');

        $cashAccount->delete();

        return redirect()
            ->route('finance.cash-accounts.index')
            ->with('status', 'Rekening kas berhasil dihapus.');
    }

    private function form(CashAccount $cashAccount): View
    {
        $chartAccounts = ChartAccount::query()
            ->where('is_cash_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('finance.generic.form', [
            'title' => $cashAccount->exists ? 'Edit Kas dan Rekening' : 'Tambah Kas dan Rekening',
            'description' => 'Rekening harus terhubung ke akun jurnal yang ditandai sebagai akun kas.',
            'action' => $cashAccount->exists
                ? route('finance.cash-accounts.update', $cashAccount)
                : route('finance.cash-accounts.store'),
            'method' => $cashAccount->exists ? 'PUT' : 'POST',
            'cancelRoute' => $cashAccount->exists
                ? route('finance.cash-accounts.show', $cashAccount)
                : route('finance.cash-accounts.index'),
            'submitLabel' => $cashAccount->exists ? 'Simpan Perubahan' : 'Buat Rekening',
            'fields' => [
                [
                    'name' => 'chart_account_id',
                    'label' => 'Akun Jurnal',
                    'type' => 'select',
                    'required' => true,
                    'value' => $cashAccount->chart_account_id,
                    'placeholder' => 'Pilih akun kas',
                    'options' => $chartAccounts->map(fn (ChartAccount $account): array => [
                        'value' => $account->getKey(),
                        'label' => $account->code.' — '.$account->name,
                    ])->all(),
                ],
                [
                    'name' => 'name',
                    'label' => 'Nama Kas/Rekening',
                    'required' => true,
                    'value' => $cashAccount->name,
                ],
                [
                    'name' => 'bank_name',
                    'label' => 'Nama Bank',
                    'value' => $cashAccount->bank_name,
                ],
                [
                    'name' => 'account_number',
                    'label' => 'Nomor Rekening',
                    'value' => $cashAccount->account_number,
                ],
                [
                    'name' => 'opening_balance',
                    'label' => 'Saldo Awal',
                    'type' => 'number',
                    'required' => true,
                    'min' => 0,
                    'step' => '0.01',
                    'value' => $cashAccount->opening_balance ?? 0,
                    'help' => $cashAccount->transaction_lines_count ?? 0
                        ? 'Tidak dapat diubah karena rekening sudah dipakai jurnal.'
                        : 'Saldo saat ini akan mengikuti saldo awal sampai ada transaksi.',
                ],
                [
                    'name' => 'is_active',
                    'label' => 'Rekening Aktif',
                    'type' => 'checkbox',
                    'value' => $cashAccount->exists ? $cashAccount->is_active : true,
                ],
            ],
        ]);
    }

    private function ensureCashChartAccount(int $chartAccountId): void
    {
        $valid = ChartAccount::query()
            ->whereKey($chartAccountId)
            ->where('is_cash_account', true)
            ->where('is_active', true)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'chart_account_id' => 'Pilih akun jurnal aktif yang ditandai sebagai akun kas.',
            ]);
        }
    }
}
