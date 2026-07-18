@props(['headers' => []])
<div class="table-wrap"><table {{ $attributes->merge(['class'=>'data-table']) }}>@if($headers)<thead><tr>@foreach($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>@endif<tbody>{{ $slot }}</tbody></table></div>
