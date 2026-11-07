@props(['name', 'label', 'value' => '', 'type' => 'text', 'required' => true])
<div>
    <label for="{{ $name }}" class="label">{{ $label }} @if($required)<span class="text-rose-600" aria-hidden="true">*</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required) {{ $attributes->merge(['class' => 'input']) }} @error($name) aria-invalid="true" aria-describedby="error-{{ $name }}" @enderror>
    @error($name)<p id="error-{{ $name }}" class="mt-1 text-xs text-rose-700">{{ $message }}</p>@enderror
</div>
