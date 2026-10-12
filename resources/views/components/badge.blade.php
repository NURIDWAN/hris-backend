@props([
    'type' => 'secondary'
])

<span {{ $attributes->merge(['class' => "badge badge-{$type}"]) }}>
    {{ $slot }}
</span>
