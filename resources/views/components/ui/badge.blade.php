@props(['variant' => 'muted'])
@php($class = ['success'=>'badge badge-success','warning'=>'badge badge-warning','danger'=>'badge badge-danger','muted'=>'badge badge-muted','info'=>'badge badge-info'][$variant] ?? 'badge badge-muted')
<span {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</span>
