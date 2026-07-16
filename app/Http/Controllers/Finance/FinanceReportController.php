<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\CashAccount;
use App\Models\Finance\FinancialTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FinanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $dateFrom = $request->date('date_from')?->startOfDay();
        $dateTo = $request->date('date_to')?->endOfDay();

        $transactions = FinancialTransaction::query()
            ->with(['lines.account', 'creator'])
            ->when(
                $request->string('status')->toString(),
                fn ($query, string $status) => $query->where('status', $status),
            )
            ->when(
                $request->string('transaction_type')->toString(),
                fn ($query, string $type) => $query->where('transaction_type', $type),
            )
            ->when($dateFrom, fn ($query) => $query->whereDate('transaction_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('transaction_date', '<=', $dateTo))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $cashAccounts = CashAccount::query()
            ->with('chartAccount')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('finance.reports.index', compact(
            'transactions',
            'cashAccounts',
        ));
    }
}
