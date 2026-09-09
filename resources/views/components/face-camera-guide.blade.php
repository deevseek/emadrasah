@props([
    'videoId',
    'statusId' => null,
    'status' => 'Posisikan wajah di dalam area.',
    'class' => '',
])

<div {{ $attributes->class(['face-camera-capture', $class]) }} data-face-camera data-guide-state="ready">
    <video id="{{ $videoId }}" class="face-camera-capture__video" autoplay muted playsinline></video>
    <div class="face-camera-capture__mask" aria-hidden="true">
        <div class="face-camera-capture__oval"></div>
    </div>
</div>
<p @if($statusId) id="{{ $statusId }}" @endif class="face-camera-capture__status" data-camera-status aria-live="polite">{{ $status }}</p>
