<?php

declare(strict_types=1);

namespace App\Enums\Finance;

enum FeeCategory: string { case Spp='spp'; case Registration='daftar_ulang'; case Activity='kegiatan'; case Book='buku'; case Uniform='seragam'; case Exam='ujian'; case Extracurricular='ekstrakurikuler'; case Donation='donasi'; case Other='lainnya'; }
enum BillingFrequency: string { case Once='sekali'; case Monthly='bulanan'; case Semester='semester'; case Yearly='tahunan'; case Incidental='insidental'; }
enum InvoiceStatus: string { case Draft='draft'; case Unpaid='unpaid'; case PartiallyPaid='partially_paid'; case Paid='paid'; case Overdue='overdue'; case Cancelled='cancelled'; }
enum PaymentMethod: string { case Cash='tunai'; case BankTransfer='transfer_bank'; case Qris='qris'; case VirtualAccount='virtual_account'; case Other='lainnya'; }
enum PaymentStatus: string { case Posted='posted'; case Cancelled='cancelled'; case Refunded='refunded'; }
enum DiscountType: string { case Fixed='nominal'; case Percentage='persentase'; }
enum ApprovalStatus: string { case Draft='draft'; case Approved='approved'; case Rejected='rejected'; case Expired='expired'; case Cancelled='cancelled'; }
enum AccountType: string { case Asset='asset'; case Liability='liability'; case Equity='equity'; case Revenue='revenue'; case Expense='expense'; }
enum NormalBalance: string { case Debit='debit'; case Credit='credit'; }
enum TransactionType: string { case CashIn='cash_in'; case CashOut='cash_out'; case Transfer='transfer'; case Adjustment='adjustment'; case OpeningBalance='opening_balance'; }
enum TransactionStatus: string { case Draft='draft'; case Posted='posted'; case Cancelled='cancelled'; }
enum SalaryComponentType: string { case Earning='earning'; case Deduction='deduction'; }
enum SalaryCalculationType: string { case Fixed='fixed'; case Percentage='percentage'; case Attendance='attendance'; case Manual='manual'; }
enum PayrollStatus: string { case Draft='draft'; case Calculated='calculated'; case Reviewed='reviewed'; case Approved='approved'; case Paid='paid'; case Closed='closed'; case Cancelled='cancelled'; }
