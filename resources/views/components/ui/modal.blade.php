@props([
    'show' => false,
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'maxWidth' => 'md', // sm, md, lg, xl, 2xl
    'onClose' => null,
])

@php
    $widthClasses = match($maxWidth) {
        'sm' => 'max-w-sm',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        default => 'max-w-md',
    };
@endphp

@if($show)
    <div 
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/75 backdrop-blur-xs p-3 sm:p-4 overflow-y-auto animate-fadeIn"
        x-data
        @keydown.escape.window="@if($onClose) {{ $onClose }} @endif"
    >
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 {{ $widthClasses }} w-full shadow-2xl overflow-hidden my-auto transform transition-all">
            <!-- CABECERA -->
            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <div class="flex items-center gap-3">
                    @if($icon)
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/80 dark:text-indigo-400 flex items-center justify-center font-bold text-base shrink-0">
                            {{ $icon }}
                        </div>
                    @endif
                    <div>
                        <h4 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
                            {{ $title }}
                        </h4>
                        @if($subtitle)
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ $subtitle }}
                            </p>
                        @endif
                    </div>
                </div>

                @if($onClose)
                    <button 
                        type="button" 
                        wire:click="{{ $onClose }}" 
                        class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
                        title="Cerrar ventana"
                    >
                        <span class="text-lg font-bold">&times;</span>
                    </button>
                @endif
            </div>

            <!-- CUERPO -->
            <div class="p-5 sm:p-6 max-h-[calc(90vh-140px)] overflow-y-auto space-y-4">
                {{ $slot }}
            </div>

            <!-- PIE DE VENTANA (OPCIONAL) -->
            @if(isset($footer))
                <div class="px-5 sm:px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/40 flex items-center justify-end gap-2.5">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
@endif
