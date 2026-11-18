@props([
    'variant' => 'neutral', // success, warning, danger, info, purple, amber, neutral
    'size' => 'sm',        // xs, sm, md
    'dot' => false,
])

@php
    $baseClasses = 'inline-flex items-center font-bold tracking-tight rounded-lg transition-colors';

    $sizeClasses = match($size) {
        'xs' => 'px-1.5 py-0.5 text-[10px] gap-1',
        'md' => 'px-3 py-1.5 text-xs gap-1.5',
        default => 'px-2 py-0.5 text-[11px] gap-1.5', // sm
    };

    $variantClasses = match($variant) {
        'success', 'emerald' => 'bg-emerald-50 text-emerald-700 border border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60',
        'warning', 'amber' => 'bg-amber-50 text-amber-800 border border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60',
        'danger', 'rose', 'red' => 'bg-rose-50 text-rose-700 border border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60',
        'info', 'blue', 'sky' => 'bg-sky-50 text-sky-700 border border-sky-200/80 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60',
        'purple', 'indigo' => 'bg-indigo-50 text-indigo-700 border border-indigo-200/80 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-800/60',
        default => 'bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800/70 dark:text-slate-300 dark:border-slate-700/60',
    };

    $dotColors = match($variant) {
        'success', 'emerald' => 'bg-emerald-500',
        'warning', 'amber' => 'bg-amber-500',
        'danger', 'rose', 'red' => 'bg-rose-500',
        'info', 'blue', 'sky' => 'bg-sky-500',
        'purple', 'indigo' => 'bg-indigo-500',
        default => 'bg-slate-400',
    };
@endphp

<span {{ $attributes->merge(['class' => "$baseClasses $sizeClasses $variantClasses"]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColors }} shrink-0"></span>
    @endif
    {{ $slot }}
</span>
