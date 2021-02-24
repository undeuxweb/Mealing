@props([
    'icon',
    'name',
    'level' => null,
])

@switch($level)
    @case('may')
        <span class="allergen-level-may">
            <i class="{{ $icon }}" title="May Contain {{ $name }}"></i>
        </span>
        @break
    @case('yes')
        <span class="allergen-level-yes">
            <i class="{{ $icon }}" title="Contains {{ $name }}"></i>
        </span>
        @break
    @default
        <span class="allergen-level-no">
            <i class="{{ $icon }}" title="Doesn't Contain {{ $name }}"></i>
        </span>
@endswitch