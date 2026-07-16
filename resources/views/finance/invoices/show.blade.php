@component('finance._page', ['title' => 'Detail Tagihan'])
<p>{{ $invoice->invoice_number }} - {{ $invoice->student->name }} - {{ $invoice->status }}</p><p>Sisa: Rp {{ number_format((float) $invoice->outstanding_amount, 0, ',', '.') }}</p>
@endcomponent