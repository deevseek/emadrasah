@props(['padding' => true])
<section {{ $attributes->merge(['class' => 'card']) }}><div @class(['card-body' => $padding])>{{ $slot }}</div></section>
