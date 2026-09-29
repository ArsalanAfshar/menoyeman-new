@props(['type' => 'info'])
@php
    $styles = match ($type) {
        'success' => 'bg-success-50 text-success-700 border-success-600/20',
        'error', 'danger' => 'bg-danger-50 text-danger-700 border-danger-600/20',
        'warning' => 'bg-warning-50 text-warning-700 border-warning-500/30',
        default => 'bg-brand-50 text-brand-800 border-brand-200',
    };
@endphp
<div {{ $attributes->merge(['class' => "rounded-xl border px-4 py-3 text-sm $styles"]) }} role="alert">
    {{ $slot }}
</div>
