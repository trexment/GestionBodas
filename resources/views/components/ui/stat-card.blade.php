@props([
    'label' => '',
    'value' => '',
    'subtext' => null,
    'icon' => null,
    'variant' => 'neutral', // neutral, emerald, indigo, amber, purple, rose
])

@php
    $theme = match($variant) {
        'emerald', 'success' => [
            'card' => 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800',
            'value' => 'text-emerald-600 dark:text-emerald-400',
            'iconBg' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
            'topBorder' => 'border-t-2 border-t-emerald-500',
        ],
        'indigo', 'primary' => [
            'card' => 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800',
            'value' => 'text-indigo-600 dark:text-indigo-400',
            'iconBg' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400',
            'topBorder' => 'border-t-2 border-t-indigo-500',
        ],
        'amber', 'warning' => [
            'card' => 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800',
            'value' => 'text-amber-600 dark:text-amber-400',
            'iconBg' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
            'topBorder' => 'border-t-2 border-t-amber-500',
        ],
        'purple' => [
            'card' => 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800',
            'value' => 'text-purple-600 dark:text-purple-400',
            'iconBg' => 'bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400',
            'topBorder' => 'border-t-2 border-t-purple-500',
        ],
        'rose', 'danger' => [
            'card' => 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800',
            'value' => 'text-rose-600 dark:text-rose-400',
            'iconBg' => 'bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400',
            'topBorder' => 'border-t-2 border-t-rose-500',
        ],
        default => [
            'card' => 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800',
            'value' => 'text-slate-900 dark:text-white',
            'iconBg' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
            'topBorder' => '',
        ],
    };
@endphp

<div {{ $attributes->merge(['class' => "p-4 sm:p-5 rounded-2xl border shadow-xs transition hover:shadow-md {$theme['card']} {$theme['topBorder']}"]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="space-y-1 w-full">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 block">
                {{ $label }}
            </span>
            <div class="text-xl sm:text-2xl font-black tracking-tight {{ $theme['value'] }}">
                {{ $value }}
            </div>
            @if($subtext)
                <p class="text-[11px] text-slate-500 dark:text-slate-400 pt-0.5">
                    {{ $subtext }}
                </p>
            @endif

            @if(isset($slot) && $slot->isNotEmpty())
                <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 space-y-1 text-[11px] text-slate-500 dark:text-slate-400">
                    {{ $slot }}
                </div>
            @endif
        </div>

        @if($icon)
            <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base shrink-0 {{ $theme['iconBg'] }}">
                {{ $icon }}
            </div>
        @endif
    </div>
</div>
