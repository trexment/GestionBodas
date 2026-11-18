@props([
    'variant' => 'primary', // primary, secondary, outline, ghost, danger, success
    'size' => 'md',        // xs, sm, md, lg
    'type' => 'button',
    'icon' => null,
    'iconRight' => null,
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-bold tracking-tight rounded-xl transition duration-150 cursor-pointer select-none disabled:opacity-50 disabled:cursor-not-allowed focus:outline-hidden focus:ring-2 focus:ring-offset-1';

    $sizeClasses = match($size) {
        'xs' => 'px-2.5 py-1 text-[11px] gap-1.5 shadow-2xs',
        'sm' => 'px-3 py-1.5 text-xs gap-1.5 shadow-xs',
        'lg' => 'px-5 py-3 text-sm gap-2.5 shadow-md',
        default => 'px-4 py-2 text-xs gap-2 shadow-xs', // md
    };

    $variantClasses = match($variant) {
        'primary' => 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500 shadow-indigo-500/20 active:bg-indigo-800',
        'secondary' => 'bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 focus:ring-slate-400 active:bg-slate-300 dark:active:bg-slate-600',
        'outline' => 'bg-transparent border border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-indigo-500',
        'ghost' => 'bg-transparent hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white focus:ring-slate-400 shadow-none',
        'danger' => 'bg-rose-600 hover:bg-rose-700 text-white focus:ring-rose-500 shadow-rose-500/20 active:bg-rose-800',
        'success' => 'bg-emerald-600 hover:bg-emerald-700 text-white focus:ring-emerald-500 shadow-emerald-500/20 active:bg-emerald-800',
        'amber', 'warning' => 'bg-amber-600 hover:bg-amber-700 text-white focus:ring-amber-500 shadow-amber-500/20 active:bg-amber-800',
        default => 'bg-indigo-600 hover:bg-indigo-700 text-white',
    };
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => "$baseClasses $sizeClasses $variantClasses"]) }}>
    @if($icon)
        <span class="shrink-0">{{ $icon }}</span>
    @endif

    {{ $slot }}

    @if($iconRight)
        <span class="shrink-0">{{ $iconRight }}</span>
    @endif
</button>
