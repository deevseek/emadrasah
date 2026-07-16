<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\AccountType;
use App\Enums\Finance\NormalBalance;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ChartAccountRequest;
use App\Models\Finance\ChartAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ChartAccountController extends Controller
{
    public function index(Request $request): View
    {
        $items = ChartAccount::query()
            ->with('parent')
            ->withCount([
                'children',
                'lines',
                'cashAccounts',
                'feeTypes',
                'salaryExpenseComponents',
                'salaryPayableComponents',
            ])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->paginate(30)
            ->withQueryString();

        return view('finance.generic.index', [
            'title' => 'Bagan Akun',
            'description' => 'Kelola struktur akun untuk jurnal, kas, pendapatan, dan penggajian.',
            'items' => $items,
            'headers' => ['Kode', 'Nama', 'Induk', 'Tipe', 'Saldo Normal', 'Kas', 'Status'],
            'rowBuilder' => static fn (ChartAccount $account): array => [
                $account->code,
                $account->name,
                $account->parent?->code.' — '.$account->parent?->name ?: '-',
                str($account->account_type)->title(),
                str($account->normal_balance)->title(),
                $account->is_cash_account ? 'Ya' : 'Tidak',
                [
                    'value' => $account->is_active ? 'Aktif' : 'Nonaktif',
                    'variant' => $account->is_active ? 'success' : 'muted',
                ],
            ],
            'createRoute' => route('finance.chart-accounts.create'),
            'createLabel' => 'Tambah Akun',
            'showRouteName' => 'finance.chart-accounts.show',
            'editRouteName' => 'finance.chart-accounts.edit',
            'toggleRouteName' => 'finance.chart-accounts.toggle',
            'destroyRouteName' => 'finance.chart-accounts.destroy',
            'viewPermission' => 'finance-accounts.view',
            'managePermission' => 'finance-accounts.manage',
            'canDelete' => static fn (ChartAccount $account): bool => self::isUnused($account),
            'searchPlaceholder' => 'Kode atau nama akun',
            'emptyTitle' => 'Belum ada bagan akun',
        ]);
    }

    public function create(): View
    {
        return $this->form(new ChartAccount);
    }

    public function store(ChartAccountRequest $request): RedirectResponse
    {
        $account = ChartAccount::create($request->validated());

        activity('finance')
            ->performedOn($account)
            ->causedBy($request->user())
            ->event('chart-account.created')
            ->log('Akun jurnal dibuat');

        return redirect()
            ->route('finance.chart-accounts.show', $account)
            ->with('status', 'Akun berhasil dibuat.');
    }

    public function show(ChartAccount $chartAccount): View
    {
        $chartAccount->load('parent')->loadCount([
            'children',
            'lines',
            'cashAccounts',
            'feeTypes',
            'salaryExpenseComponents',
            'salaryPayableComponents',
        ]);

        return view('finance.generic.show', [
            'title' => 'Detail Bagan Akun',
            'description' => $chartAccount->code.' — '.$chartAccount->name,
            'details' => [
                'Kode' => $chartAccount->code,
                'Nama' => $chartAccount->name,
                'Akun Induk' => $chartAccount->parent
                    ? $chartAccount->parent->code.' — '.$chartAccount->parent->name
                    : '-',
                'Tipe Akun' => str($chartAccount->account_type)->title(),
                'Saldo Normal' => str($chartAccount->normal_balance)->title(),
                'Akun Kas' => $chartAccount->is_cash_account ? 'Ya' : 'Tidak',
                'Urutan' => $chartAccount->sequence,
                'Jumlah Subakun' => $chartAccount->children_count,
                'Baris Jurnal' => $chartAccount->lines_count,
                'Rekening Kas' => $chartAccount->cash_accounts_count,
            ],
            'status' => [
                'label' => $chartAccount->is_active ? 'Aktif' : 'Nonaktif',
                'variant' => $chartAccount->is_active ? 'success' : 'muted',
            ],
            'indexRoute' => route('finance.chart-accounts.index'),
            'editRoute' => route('finance.chart-accounts.edit', $chartAccount),
            'toggleRoute' => route('finance.chart-accounts.toggle', $chartAccount),
            'destroyRoute' => route('finance.chart-accounts.destroy', $chartAccount),
            'isActive' => $chartAccount->is_active,
            'canDelete' => self::isUnused($chartAccount),
            'viewPermission' => 'finance-accounts.view',
            'managePermission' => 'finance-accounts.manage',
            'deleteConfirmation' => 'Hapus akun ini dari bagan akun?',
        ]);
    }

    public function edit(ChartAccount $chartAccount): View
    {
        return $this->form($chartAccount);
    }

    public function update(
        ChartAccountRequest $request,
        ChartAccount $chartAccount,
    ): RedirectResponse {
        $data = $request->validated();
        $this->ensureNoHierarchyCycle($chartAccount, $data['parent_id'] ?? null);
        $chartAccount->update($data);

        activity('finance')
            ->performedOn($chartAccount)
            ->causedBy($request->user())
            ->event('chart-account.updated')
            ->log('Akun jurnal diperbarui');

        return redirect()
            ->route('finance.chart-accounts.show', $chartAccount)
            ->with('status', 'Akun berhasil diperbarui.');
    }

    public function toggle(Request $request, ChartAccount $chartAccount): RedirectResponse
    {
        abort_unless($request->user()?->can('finance-accounts.manage'), 403);

        if ($chartAccount->is_active && $chartAccount->cashAccounts()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'chart_account' => 'Nonaktifkan seluruh rekening kas yang memakai akun ini terlebih dahulu.',
            ]);
        }

        $chartAccount->update(['is_active' => ! $chartAccount->is_active]);

        activity('finance')
            ->performedOn($chartAccount)
            ->causedBy($request->user())
            ->event($chartAccount->is_active ? 'chart-account.activated' : 'chart-account.deactivated')
            ->log($chartAccount->is_active ? 'Akun jurnal diaktifkan' : 'Akun jurnal dinonaktifkan');

        return back()->with('status', 'Status akun diperbarui.');
    }

    public function destroy(Request $request, ChartAccount $chartAccount): RedirectResponse
    {
        abort_unless($request->user()?->can('finance-accounts.manage'), 403);
        $chartAccount->loadCount([
            'children',
            'lines',
            'cashAccounts',
            'feeTypes',
            'salaryExpenseComponents',
            'salaryPayableComponents',
        ]);

        if (! self::isUnused($chartAccount)) {
            throw ValidationException::withMessages([
                'chart_account' => 'Akun sudah direferensikan dan tidak dapat dihapus. Nonaktifkan sebagai gantinya.',
            ]);
        }

        activity('finance')
            ->performedOn($chartAccount)
            ->causedBy($request->user())
            ->event('chart-account.deleted')
            ->log('Akun jurnal dihapus');

        $chartAccount->delete();

        return redirect()
            ->route('finance.chart-accounts.index')
            ->with('status', 'Akun berhasil dihapus.');
    }

    private function form(ChartAccount $chartAccount): View
    {
        $parents = ChartAccount::query()
            ->when($chartAccount->exists, fn ($query) => $query->whereKeyNot($chartAccount->getKey()))
            ->orderBy('code')
            ->get();

        return view('finance.generic.form', [
            'title' => $chartAccount->exists ? 'Edit Bagan Akun' : 'Tambah Bagan Akun',
            'description' => 'Kode harus unik dan struktur induk tidak boleh membentuk siklus.',
            'action' => $chartAccount->exists
                ? route('finance.chart-accounts.update', $chartAccount)
                : route('finance.chart-accounts.store'),
            'method' => $chartAccount->exists ? 'PUT' : 'POST',
            'cancelRoute' => $chartAccount->exists
                ? route('finance.chart-accounts.show', $chartAccount)
                : route('finance.chart-accounts.index'),
            'submitLabel' => $chartAccount->exists ? 'Simpan Perubahan' : 'Buat Akun',
            'fields' => [
                [
                    'name' => 'parent_id',
                    'label' => 'Akun Induk',
                    'type' => 'select',
                    'value' => $chartAccount->parent_id,
                    'placeholder' => 'Tanpa akun induk',
                    'options' => $parents->map(fn (ChartAccount $parent): array => [
                        'value' => $parent->getKey(),
                        'label' => $parent->code.' — '.$parent->name,
                    ])->all(),
                ],
                [
                    'name' => 'sequence',
                    'label' => 'Urutan',
                    'type' => 'number',
                    'min' => 0,
                    'value' => $chartAccount->sequence ?? 0,
                ],
                [
                    'name' => 'code',
                    'label' => 'Kode Akun',
                    'required' => true,
                    'value' => $chartAccount->code,
                ],
                [
                    'name' => 'name',
                    'label' => 'Nama Akun',
                    'required' => true,
                    'value' => $chartAccount->name,
                ],
                [
                    'name' => 'account_type',
                    'label' => 'Tipe Akun',
                    'type' => 'select',
                    'required' => true,
                    'value' => $chartAccount->account_type,
                    'options' => collect(AccountType::cases())->map(fn (AccountType $type): array => [
                        'value' => $type->value,
                        'label' => str($type->value)->title()->toString(),
                    ])->all(),
                ],
                [
                    'name' => 'normal_balance',
                    'label' => 'Saldo Normal',
                    'type' => 'select',
                    'required' => true,
                    'value' => $chartAccount->normal_balance,
                    'options' => collect(NormalBalance::cases())->map(fn (NormalBalance $balance): array => [
                        'value' => $balance->value,
                        'label' => str($balance->value)->title()->toString(),
                    ])->all(),
                ],
                [
                    'name' => 'is_cash_account',
                    'label' => 'Dapat Dipakai sebagai Rekening Kas',
                    'type' => 'checkbox',
                    'value' => $chartAccount->is_cash_account,
                    'help' => 'Aktifkan hanya untuk akun aset kas atau bank.',
                ],
                [
                    'name' => 'is_active',
                    'label' => 'Akun Aktif',
                    'type' => 'checkbox',
                    'value' => $chartAccount->exists ? $chartAccount->is_active : true,
                ],
            ],
        ]);
    }

    private function ensureNoHierarchyCycle(ChartAccount $account, mixed $parentId): void
    {
        $parentId = filled($parentId) ? (int) $parentId : null;

        while ($parentId !== null) {
            if ($parentId === (int) $account->getKey()) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Akun induk membentuk siklus hierarki.',
                ]);
            }

            $parentId = ChartAccount::query()->whereKey($parentId)->value('parent_id');
            $parentId = $parentId !== null ? (int) $parentId : null;
        }
    }

    private static function isUnused(ChartAccount $account): bool
    {
        return (int) ($account->children_count ?? $account->children()->count()) === 0
            && (int) ($account->lines_count ?? $account->lines()->count()) === 0
            && (int) ($account->cash_accounts_count ?? $account->cashAccounts()->count()) === 0
            && (int) ($account->fee_types_count ?? $account->feeTypes()->count()) === 0
            && (int) ($account->salary_expense_components_count ?? $account->salaryExpenseComponents()->count()) === 0
            && (int) ($account->salary_payable_components_count ?? $account->salaryPayableComponents()->count()) === 0;
    }
}
