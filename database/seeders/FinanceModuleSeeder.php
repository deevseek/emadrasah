<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Finance\CashAccount;
use App\Models\Finance\ChartAccount;
use App\Models\Finance\FeeType;
use App\Models\Finance\SalaryComponent;
use Illuminate\Database\Seeder;

class FinanceModuleSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['1001', 'Kas Tunai', 'asset', 'debit', true], ['1002', 'Bank', 'asset', 'debit', true], ['1101', 'Piutang Siswa', 'asset', 'debit', false], ['4101', 'Pendapatan SPP', 'revenue', 'credit', false], ['4102', 'Pendapatan Pendidikan Lain', 'revenue', 'credit', false], ['4103', 'Pendapatan Donasi', 'revenue', 'credit', false], ['5101', 'Beban Gaji', 'expense', 'debit', false], ['5102', 'Beban Operasional', 'expense', 'debit', false], ['5103', 'Beban Kegiatan', 'expense', 'debit', false], ['5104', 'Beban Perlengkapan', 'expense', 'debit', false], ['3001', 'Modal/Saldo Awal', 'equity', 'credit', false],
        ];
        foreach ($accounts as $index => [$code, $name, $type, $normal, $cash]) {
            ChartAccount::updateOrCreate(['code' => $code], ['name' => $name, 'account_type' => $type, 'normal_balance' => $normal, 'is_cash_account' => $cash, 'is_active' => true, 'sequence' => $index + 1]);
        }
        $kas = ChartAccount::where('code', '1001')->first();
        CashAccount::updateOrCreate(['name' => 'Kas Tunai Bendahara'], ['chart_account_id' => $kas->id, 'opening_balance' => 0, 'current_balance' => 0, 'is_active' => true]);
        foreach ([['SPP', 'SPP Bulanan', 'spp', 'bulanan', 150000], ['DAFTAR-ULANG', 'Daftar Ulang', 'daftar_ulang', 'tahunan', 500000], ['DONASI', 'Donasi', 'donasi', 'insidental', null]] as [$code, $name, $category, $frequency, $amount]) {
            FeeType::updateOrCreate(['code' => $code], ['name' => $name, 'category' => $category, 'billing_frequency' => $frequency, 'default_amount' => $amount, 'is_mandatory' => $category !== 'donasi', 'is_active' => true, 'revenue_account_id' => ChartAccount::where('account_type', 'revenue')->first()->id]);
        }
        foreach ([['GAJI-POKOK', 'Gaji Pokok', 'earning', 'fixed'], ['UANG-MAKAN', 'Uang Makan', 'earning', 'attendance'], ['POT-ALPHA', 'Potongan Alpha', 'deduction', 'attendance'], ['POT-LAMBAT', 'Potongan Keterlambatan', 'deduction', 'attendance']] as [$code, $name, $type, $calc]) {
            SalaryComponent::updateOrCreate(['code' => $code], ['name' => $name, 'component_type' => $type, 'calculation_type' => $calc, 'default_amount' => 0, 'taxable' => false, 'is_attendance_based' => $calc === 'attendance', 'is_active' => true, 'expense_account_id' => ChartAccount::where('code', '5101')->first()->id]);
        }
    }
}
