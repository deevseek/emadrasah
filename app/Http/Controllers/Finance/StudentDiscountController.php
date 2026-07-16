<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\ApprovalStatus;
use App\Enums\Finance\DiscountType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\DiscountApprovalRequest;
use App\Http\Requests\Finance\StudentDiscountRequest;
use App\Models\AcademicYear;
use App\Models\Finance\FeeType;
use App\Models\Finance\StudentDiscount;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class StudentDiscountController extends Controller
{
    public function index(Request $request): View
    {
        $items = StudentDiscount::query()
            ->with(['student', 'feeType', 'academicYear', 'semester', 'approver'])
            ->when($request->string('search')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('reason', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('feeType', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('finance.generic.index', [
            'title' => 'Potongan dan Beasiswa',
            'description' => 'Kelola pengajuan potongan serta proses persetujuannya.',
            'items' => $items,
            'headers' => ['Siswa', 'Jenis Tagihan', 'Nilai', 'Tahun Ajaran', 'Status'],
            'rowBuilder' => static function (StudentDiscount $discount): array {
                $value = $discount->discount_type === DiscountType::Percentage->value
                    ? rtrim(rtrim((string) $discount->discount_value, '0'), '.').' %'
                    : 'Rp '.number_format((float) $discount->discount_value, 0, ',', '.');

                return [
                    $discount->student?->name ?? '-',
                    $discount->feeType?->name ?? 'Semua jenis tagihan',
                    $value,
                    $discount->academicYear?->name ?? '-',
                    [
                        'value' => str($discount->status)->title(),
                        'variant' => match ($discount->status) {
                            ApprovalStatus::Approved->value => 'success',
                            ApprovalStatus::Rejected->value => 'danger',
                            default => 'warning',
                        },
                    ],
                ];
            },
            'createRoute' => route('finance.student-discounts.create'),
            'createLabel' => 'Tambah Potongan',
            'showRouteName' => 'finance.student-discounts.show',
            'editRouteName' => 'finance.student-discounts.edit',
            'destroyRouteName' => 'finance.student-discounts.destroy',
            'viewPermission' => 'student-discounts.view',
            'managePermission' => 'student-discounts.manage',
            'canEdit' => static fn (StudentDiscount $discount): bool => $discount->status !== ApprovalStatus::Approved->value,
            'canDelete' => static fn (StudentDiscount $discount): bool => $discount->status !== ApprovalStatus::Approved->value,
            'searchPlaceholder' => 'Siswa, jenis tagihan, atau alasan',
            'emptyTitle' => 'Belum ada potongan',
        ]);
    }

    public function create(): View
    {
        return $this->form(new StudentDiscount);
    }

    public function store(StudentDiscountRequest $request): RedirectResponse
    {
        $discount = StudentDiscount::create([
            ...$request->validated(),
            'status' => ApprovalStatus::Draft->value,
            'approved_by' => null,
        ]);

        activity('student-finance')
            ->performedOn($discount)
            ->causedBy($request->user())
            ->event('discount.created')
            ->log('Potongan siswa dibuat');

        return redirect()
            ->route('finance.student-discounts.show', $discount)
            ->with('status', 'Potongan disimpan sebagai draft.');
    }

    public function show(StudentDiscount $studentDiscount): View
    {
        $studentDiscount->load([
            'student',
            'feeType',
            'academicYear',
            'semester',
            'approver',
        ]);

        $workflowActions = [];

        if ($studentDiscount->status === ApprovalStatus::Draft->value) {
            $workflowActions = [
                [
                    'label' => 'Setujui Potongan',
                    'url' => route('finance.student-discounts.approve', $studentDiscount),
                    'method' => 'PATCH',
                    'permission' => 'student-discounts.approve',
                    'confirm' => 'Setujui potongan ini?',
                ],
                [
                    'label' => 'Tolak Potongan',
                    'url' => route('finance.student-discounts.reject', $studentDiscount),
                    'method' => 'PATCH',
                    'permission' => 'student-discounts.approve',
                    'variant' => 'danger',
                    'fields' => [
                        [
                            'name' => 'reason',
                            'label' => 'Alasan Penolakan',
                            'type' => 'textarea',
                            'required' => true,
                        ],
                    ],
                ],
            ];
        }

        $discountValue = $studentDiscount->discount_type === DiscountType::Percentage->value
            ? rtrim(rtrim((string) $studentDiscount->discount_value, '0'), '.').' %'
            : 'Rp '.number_format((float) $studentDiscount->discount_value, 0, ',', '.');

        return view('finance.generic.show', [
            'title' => 'Detail Potongan',
            'description' => $studentDiscount->student?->name,
            'details' => [
                'Siswa' => $studentDiscount->student?->name,
                'Jenis Tagihan' => $studentDiscount->feeType?->name ?? 'Semua jenis tagihan',
                'Tahun Ajaran' => $studentDiscount->academicYear?->name,
                'Semester' => $studentDiscount->semester?->name ?? '-',
                'Jenis Potongan' => str($studentDiscount->discount_type)->title(),
                'Nilai Potongan' => $discountValue,
                'Batas Maksimum' => $studentDiscount->maximum_discount
                    ? 'Rp '.number_format((float) $studentDiscount->maximum_discount, 0, ',', '.')
                    : '-',
                'Masa Berlaku' => ($studentDiscount->starts_on?->format('d/m/Y') ?? '-').' s.d. '.($studentDiscount->ends_on?->format('d/m/Y') ?? '-'),
                'Alasan' => $studentDiscount->reason,
                'Disetujui Oleh' => $studentDiscount->approver?->name ?? '-',
            ],
            'status' => [
                'label' => str($studentDiscount->status)->title(),
                'variant' => match ($studentDiscount->status) {
                    ApprovalStatus::Approved->value => 'success',
                    ApprovalStatus::Rejected->value => 'danger',
                    default => 'warning',
                },
            ],
            'indexRoute' => route('finance.student-discounts.index'),
            'editRoute' => route('finance.student-discounts.edit', $studentDiscount),
            'destroyRoute' => route('finance.student-discounts.destroy', $studentDiscount),
            'canEdit' => $studentDiscount->status !== ApprovalStatus::Approved->value,
            'canDelete' => $studentDiscount->status !== ApprovalStatus::Approved->value,
            'viewPermission' => 'student-discounts.view',
            'managePermission' => 'student-discounts.manage',
            'workflowActions' => $workflowActions,
            'deleteConfirmation' => 'Hapus draft potongan ini?',
        ]);
    }

    public function edit(StudentDiscount $studentDiscount): View
    {
        $this->ensureMutable($studentDiscount);

        return $this->form($studentDiscount);
    }

    public function update(
        StudentDiscountRequest $request,
        StudentDiscount $studentDiscount,
    ): RedirectResponse {
        $this->ensureMutable($studentDiscount);

        $studentDiscount->update([
            ...$request->validated(),
            'status' => ApprovalStatus::Draft->value,
            'approved_by' => null,
        ]);

        activity('student-finance')
            ->performedOn($studentDiscount)
            ->causedBy($request->user())
            ->event('discount.updated')
            ->log('Potongan siswa diperbarui dan dikembalikan ke draft');

        return redirect()
            ->route('finance.student-discounts.show', $studentDiscount)
            ->with('status', 'Potongan diperbarui dan dikembalikan ke status draft.');
    }

    public function approve(
        DiscountApprovalRequest $request,
        StudentDiscount $studentDiscount,
    ): RedirectResponse {
        $this->ensureDraft($studentDiscount);

        $studentDiscount->update([
            'status' => ApprovalStatus::Approved->value,
            'approved_by' => $request->user()->getKey(),
        ]);

        activity('student-finance')
            ->performedOn($studentDiscount)
            ->causedBy($request->user())
            ->event('discount.approved')
            ->log('Potongan siswa disetujui');

        return back()->with('status', 'Potongan disetujui.');
    }

    public function reject(
        DiscountApprovalRequest $request,
        StudentDiscount $studentDiscount,
    ): RedirectResponse {
        $this->ensureDraft($studentDiscount);

        $studentDiscount->update([
            'status' => ApprovalStatus::Rejected->value,
            'approved_by' => null,
        ]);

        activity('student-finance')
            ->performedOn($studentDiscount)
            ->causedBy($request->user())
            ->withProperties(['reason' => $request->validated('reason')])
            ->event('discount.rejected')
            ->log('Potongan siswa ditolak');

        return back()->with('status', 'Potongan ditolak.');
    }

    public function destroy(Request $request, StudentDiscount $studentDiscount): RedirectResponse
    {
        abort_unless($request->user()?->can('student-discounts.manage'), 403);
        $this->ensureMutable($studentDiscount);

        activity('student-finance')
            ->performedOn($studentDiscount)
            ->causedBy($request->user())
            ->event('discount.deleted')
            ->log('Potongan siswa dihapus');

        $studentDiscount->delete();

        return redirect()
            ->route('finance.student-discounts.index')
            ->with('status', 'Potongan dihapus.');
    }

    private function form(StudentDiscount $studentDiscount): View
    {
        $students = Student::query()->orderBy('name')->get(['id', 'name']);
        $feeTypes = FeeType::query()->where('is_active', true)->orderBy('name')->get();
        $academicYears = AcademicYear::query()->latest('starts_on')->get();
        $semesters = Semester::query()->with('academicYear')->latest('starts_on')->get();

        return view('finance.generic.form', [
            'title' => $studentDiscount->exists ? 'Edit Potongan' : 'Tambah Potongan',
            'description' => 'Simpan sebagai draft sebelum diajukan untuk persetujuan.',
            'action' => $studentDiscount->exists
                ? route('finance.student-discounts.update', $studentDiscount)
                : route('finance.student-discounts.store'),
            'method' => $studentDiscount->exists ? 'PUT' : 'POST',
            'cancelRoute' => $studentDiscount->exists
                ? route('finance.student-discounts.show', $studentDiscount)
                : route('finance.student-discounts.index'),
            'submitLabel' => $studentDiscount->exists ? 'Simpan Perubahan' : 'Simpan Draft',
            'fields' => [
                [
                    'name' => 'student_id',
                    'label' => 'Siswa',
                    'type' => 'select',
                    'required' => true,
                    'value' => $studentDiscount->student_id,
                    'placeholder' => 'Pilih siswa',
                    'options' => $students->map(fn (Student $student): array => [
                        'value' => $student->getKey(),
                        'label' => $student->name,
                    ])->all(),
                ],
                [
                    'name' => 'fee_type_id',
                    'label' => 'Jenis Tagihan',
                    'type' => 'select',
                    'value' => $studentDiscount->fee_type_id,
                    'placeholder' => 'Semua jenis tagihan',
                    'options' => $feeTypes->map(fn (FeeType $feeType): array => [
                        'value' => $feeType->getKey(),
                        'label' => $feeType->code.' — '.$feeType->name,
                    ])->all(),
                ],
                [
                    'name' => 'academic_year_id',
                    'label' => 'Tahun Ajaran',
                    'type' => 'select',
                    'required' => true,
                    'value' => $studentDiscount->academic_year_id,
                    'placeholder' => 'Pilih tahun ajaran',
                    'options' => $academicYears->map(fn (AcademicYear $year): array => [
                        'value' => $year->getKey(),
                        'label' => $year->name,
                    ])->all(),
                ],
                [
                    'name' => 'semester_id',
                    'label' => 'Semester',
                    'type' => 'select',
                    'value' => $studentDiscount->semester_id,
                    'placeholder' => 'Tanpa semester khusus',
                    'options' => $semesters->map(fn (Semester $semester): array => [
                        'value' => $semester->getKey(),
                        'label' => ($semester->academicYear?->name ?? '-').' — '.$semester->name,
                    ])->all(),
                ],
                [
                    'name' => 'discount_type',
                    'label' => 'Jenis Potongan',
                    'type' => 'select',
                    'required' => true,
                    'value' => $studentDiscount->discount_type ?? DiscountType::Fixed->value,
                    'options' => [
                        ['value' => DiscountType::Fixed->value, 'label' => 'Nominal'],
                        ['value' => DiscountType::Percentage->value, 'label' => 'Persentase'],
                    ],
                ],
                [
                    'name' => 'discount_value',
                    'label' => 'Nilai Potongan',
                    'type' => 'number',
                    'required' => true,
                    'min' => 0,
                    'step' => '0.01',
                    'value' => $studentDiscount->discount_value,
                ],
                [
                    'name' => 'maximum_discount',
                    'label' => 'Batas Maksimum',
                    'type' => 'number',
                    'min' => 0,
                    'step' => '0.01',
                    'value' => $studentDiscount->maximum_discount,
                    'help' => 'Opsional, terutama untuk potongan persentase.',
                ],
                [
                    'name' => 'starts_on',
                    'label' => 'Mulai Berlaku',
                    'type' => 'date',
                    'value' => $studentDiscount->starts_on?->format('Y-m-d'),
                ],
                [
                    'name' => 'ends_on',
                    'label' => 'Akhir Berlaku',
                    'type' => 'date',
                    'value' => $studentDiscount->ends_on?->format('Y-m-d'),
                ],
                [
                    'name' => 'reason',
                    'label' => 'Alasan',
                    'type' => 'textarea',
                    'required' => true,
                    'value' => $studentDiscount->reason,
                    'span' => 2,
                ],
            ],
        ]);
    }

    private function ensureDraft(StudentDiscount $studentDiscount): void
    {
        if ($studentDiscount->status !== ApprovalStatus::Draft->value) {
            throw ValidationException::withMessages([
                'discount' => 'Hanya potongan berstatus draft yang dapat diproses.',
            ]);
        }
    }

    private function ensureMutable(StudentDiscount $studentDiscount): void
    {
        if ($studentDiscount->status === ApprovalStatus::Approved->value) {
            throw ValidationException::withMessages([
                'discount' => 'Potongan yang sudah disetujui tidak dapat diubah atau dihapus.',
            ]);
        }
    }
}
