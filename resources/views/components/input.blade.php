@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'required' => false,
    'placeholder' => '',
    'autocomplete' => null,
])

@if ($label)
    <label class="block mb-2 font-semibold" for="{{ $name }}">
        {{ $label }}
    </label>
@endif

<div class="{{ $type === 'password' ? 'relative' : '' }}">
    <input type="{{ $type }}" name="{{ $name }}" id="{{ $name }}" value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}" @required($required)
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        @if ($errors->has($name)) aria-describedby="{{ $name }}-error" @endif
        {{ $attributes->merge([
            'class' =>
                'rounded-lg p-2 w-full focus:ring-2 focus:outline-none border ' .
                ($type === 'password' ? 'pr-10 ' : '') .
                ($errors->has($name) ? 'border-red-500 focus:ring-red-400' : 'border-gray-300 focus:ring-gray-400'),
        ]) }}>

    @if ($type === 'password')
        <button type="button"
            onclick="
                const input = document.getElementById('{{ $name }}');
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                this.querySelector('[data-icon-show]').classList.toggle('hidden', isPassword);
                this.querySelector('[data-icon-hide]').classList.toggle('hidden', !isPassword);
                this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            "
            aria-label="Show password"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 focus:outline-none">
            <span data-icon-show>
                <x-dynamic-component :component="'fas-eye'" class="w-5 h-5" />
            </span>
            <span data-icon-hide class="hidden">
                <x-dynamic-component :component="'fas-eye-slash'" class="w-5 h-5" />
            </span>
        </button>
    @endif
</div>

@error($name)
    <p id="{{ $name }}-error" class="text-red-600 text-sm mt-1">
        {{ $message }}
    </p>
@enderror
