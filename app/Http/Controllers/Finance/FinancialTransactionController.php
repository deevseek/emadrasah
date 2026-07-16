<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\FinancialTransactionCancellationRequest;
use App\Http\Requests\Finance\FinancialTransactionRequest;
use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\FinancialTransaction;
use App\Services\Finance\FinancialTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FinancialTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $items = FinancialTransaction::query()
            ->with(['lines.account', 'creator', 'poster', 'reversalTransaction'])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('transaction_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->string('status')->toString(),
                fn ($query, string $status) => $query->where('status', $status),
            )
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('finance.generic.index', [
            'title' => 'Jurnal Transaksi',
            'description' => 'Daftar jurnal posted dan reversal dengan audit trail yang tidak dapat dihapus.',
            'items' => $items,
            'headers' => ['Nomor', 'Tanggal', 'Tipe', 'Keterangan', 'Nilai', 'Status'],
            'rowBuilder' => static fn (FinancialTransaction $transaction): array => [
                $transaction->transaction_number,
                $transaction->transaction_date?->format('d/m/Y'),
                str($transaction->transaction_type)->replace('_', ' ')->title()->toString(),
                str($transaction->description)->limit(60)->toString(),
                'Rp '.number_format((float) $transaction->lines->sum('debit'), 0, ',', '.'),
                [
                    'value' => str($transaction->status)->title()->toString(),
                    'variant' => $transaction->status === TransactionStatus::Posted->value
                        ? 'success'
                        : 'muted',
                ],
            ],
            'createRoute' => route('finance.transactions.create'),
            'createLabel' => 'Buat Jurnal',
            'showRouteName' => 'finance.transactions.show',
            'viewPermission' => 'finance-transactions.view',
            'managePermission' => 'finance-transactions.create',
            'searchPlaceholder' => 'Nomor atau keterangan jurnal',
            'emptyTitle' => 'Belum ada jurnal transaksi',
        ]);
    }

    public function create(): View
    {
        return view('finance.transactions.form', [
            'chartAccounts' => ChartAccount::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
            'cashAccounts' => CashAccount::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        FinancialTransactionRequest $request,
        FinancialTransactionService $service,
    ): RedirectResponse {
        $validated = $request->validated();
        $lines = $validated['lines'];
        unset($validated['lines']);

        $transaction = $service->createAndPost([
            ...$validated,
            'created_by' => $request->user()->getKey(),
        ], $lines);

        return redirect()
            ->route('finance.transactions.show', $transaction)
            ->with('status', 'Transaksi berhasil diposting.');
    }

    public function show(FinancialTransaction $transaction): View
    {
        $transaction->load([
            'lines.account',
            'lines.cashAccount',
            'creator',
            'poster',
            'canceller',
            'reversalTransaction',
        ]);

        return view('finance.transactions.show', compact('transaction'));
    }

    public function cancel(
        FinancialTransactionCancellationRequest $request,
        FinancialTransaction $transaction,
        FinancialTransactionService $service,
    ): RedirectResponse {
        $reversal = $service->reverse(
            $transaction,
            $request->validated('reason'),
        );

        return redirect()
            ->route('finance.transactions.show', $reversal)
            ->with('status', 'Transaksi dibatalkan dan jurnal reversal dibuat.');
    }
}
