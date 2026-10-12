@props([
    'striped' => false
])

<div class="table-container">
    <table {{ $attributes->merge(['class' => 'table' . ($striped ? ' table-striped' : '')]) }}>
        @if(isset($header))
            <thead>
                <tr>
                    {{ $header }}
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
