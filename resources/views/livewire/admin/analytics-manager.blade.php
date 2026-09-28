<div class="space-y-8">
    
    <!-- ENCABEZADO Y SELECTOR DE AÑO -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <h2 class="text-2xl font-extrabold text-slate-900 flex items-center gap-2">
                <span>📈</span> Estadísticas de Temporada y Tendencias Musicales
            </h2>
            <p class="text-xs text-slate-500 mt-1">Análisis de rendimiento económico, estacionalidad y repertorio más solicitado por las parejas.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 uppercase">Temporada:</span>
            <select wire:model.live="selectedYear" class="bg-white border border-slate-300 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500">
                @for($y = (int)date('Y') + 1; $y >= 2024; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>
    </div>

    <!-- TARJETAS DE RESUMEN ECONÓMICO ANUAL -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">Facturación {{ $selectedYear }}</span>
            <div class="text-2xl font-extrabold text-slate-900">{{ number_format($totalBilledYear, 2, ',', '.') }} €</div>
            <div class="text-xs text-slate-500 mt-1">Total emitido en el año</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 block mb-1">Cobrado / Liquidado</span>
            <div class="text-2xl font-extrabold text-emerald-600">{{ number_format($paidYear, 2, ',', '.') }} €</div>
            <div class="text-xs text-slate-500 mt-1">Facturas cobradas</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-rose-600 block mb-1">Pendiente de Cobro</span>
            <div class="text-2xl font-extrabold {{ $pendingYear > 0 ? 'text-rose-600' : 'text-slate-800' }}">{{ number_format($pendingYear, 2, ',', '.') }} €</div>
            <div class="text-xs text-slate-500 mt-1">Por liquidar</div>
        </div>

        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-xs">
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 block mb-1">Eventos del Año</span>
            <div class="text-2xl font-extrabold text-indigo-600">{{ $totalEventsYear }}</div>
            <div class="text-xs text-slate-500 mt-1">Bodas y celebraciones</div>
        </div>
    </div>

    <!-- GRÁFICA DE FACTURACIÓN MENSUAL (BAR CHART) -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200 shadow-xs space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <span>📊</span> Distribución de Ingresos Mensuales (Temporada {{ $selectedYear }})
            </h3>
            <span class="text-xs text-slate-400">Evolución mes a mes</span>
        </div>

        @php
            $maxMonthly = max(array_values($monthlyBilled)) ?: 1;
        @endphp

        <div class="grid grid-cols-6 sm:grid-cols-12 gap-2 sm:gap-3 items-end pt-8 pb-2" style="height: 220px;">
            @foreach($monthlyBilled as $month => $val)
                @php
                    $heightPct = $val > 0 ? max(10, min(100, round(($val / $maxMonthly) * 100))) : 4;
                @endphp
                <div class="flex flex-col items-center h-full justify-end group">
                    <div class="text-[10px] font-bold text-slate-500 mb-1 opacity-0 group-hover:opacity-100 transition truncate">
                        {{ $val > 0 ? number_format($val, 0) . '€' : '' }}
                    </div>
                    <div class="w-full rounded-t-xl transition duration-300 {{ $val > 0 ? 'bg-gradient-to-t from-indigo-600 to-purple-500 group-hover:from-indigo-500 group-hover:to-pink-500 shadow-xs' : 'bg-slate-100' }}" style="height: {{ $heightPct }}%;"></div>
                    <span class="text-[11px] font-bold text-slate-500 mt-2 uppercase">{{ $month }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- TOP REPERTORIO MUSICAL: MOMENTOS CLAVE -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- TOP BAILE NUPCIAL -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <span class="text-xl">💃</span>
                <h4 class="text-sm font-bold text-slate-900">Top Baile Nupcial (Apertura)</h4>
            </div>
            <ul class="divide-y divide-slate-100 text-xs">
                @forelse($topDance as $i => $song)
                    <li class="py-2.5 flex items-center justify-between gap-2">
                        <div class="truncate">
                            <span class="font-mono text-[10px] font-bold text-indigo-500 mr-1.5">#{{ $i+1 }}</span>
                            <strong class="text-slate-800">{{ $song->title }}</strong>
                            @if($song->artist) <span class="text-slate-400 block text-[11px]">{{ $song->artist }}</span> @endif
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 font-bold text-[11px] flex-shrink-0">{{ $song->total }} bodas</span>
                    </li>
                @empty
                    <li class="py-4 text-center text-slate-400">Aún no hay datos suficientes.</li>
                @endforelse
            </ul>
        </div>

        <!-- TOP ENTRADA SALÓN -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <span class="text-xl">🍽️</span>
                <h4 class="text-sm font-bold text-slate-900">Top Entrada al Comedor</h4>
            </div>
            <ul class="divide-y divide-slate-100 text-xs">
                @forelse($topEntrance as $i => $song)
                    <li class="py-2.5 flex items-center justify-between gap-2">
                        <div class="truncate">
                            <span class="font-mono text-[10px] font-bold text-purple-500 mr-1.5">#{{ $i+1 }}</span>
                            <strong class="text-slate-800">{{ $song->title }}</strong>
                            @if($song->artist) <span class="text-slate-400 block text-[11px]">{{ $song->artist }}</span> @endif
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 font-bold text-[11px] flex-shrink-0">{{ $song->total }} bodas</span>
                    </li>
                @empty
                    <li class="py-4 text-center text-slate-400">Aún no hay datos suficientes.</li>
                @endforelse
            </ul>
        </div>

        <!-- TOP TARTA -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <span class="text-xl">🎂</span>
                <h4 class="text-sm font-bold text-slate-900">Top Corte de Tarta</h4>
            </div>
            <ul class="divide-y divide-slate-100 text-xs">
                @forelse($topCake as $i => $song)
                    <li class="py-2.5 flex items-center justify-between gap-2">
                        <div class="truncate">
                            <span class="font-mono text-[10px] font-bold text-pink-500 mr-1.5">#{{ $i+1 }}</span>
                            <strong class="text-slate-800">{{ $song->title }}</strong>
                            @if($song->artist) <span class="text-slate-400 block text-[11px]">{{ $song->artist }}</span> @endif
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-pink-50 text-pink-700 font-bold text-[11px] flex-shrink-0">{{ $song->total }} bodas</span>
                    </li>
                @empty
                    <li class="py-4 text-center text-slate-400">Aún no hay datos suficientes.</li>
                @endforelse
            </ul>
        </div>

        <!-- TOP TEMAZOS FIESTA -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4 md:col-span-2">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <span class="text-xl">🔥</span>
                <h4 class="text-sm font-bold text-slate-900">Temazos Imprescindibles en Barra Libre</h4>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                @forelse($topParty as $i => $song)
                    <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between gap-2">
                        <div class="truncate">
                            <span class="font-mono text-[10px] font-bold text-indigo-500 mr-1">#{{ $i+1 }}</span>
                            <strong class="text-slate-800">{{ $song->title }}</strong>
                            @if($song->artist) <span class="text-slate-400 block text-[11px]">{{ $song->artist }}</span> @endif
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 font-extrabold text-[11px]">{{ $song->total }}</span>
                    </div>
                @empty
                    <div class="col-span-2 py-4 text-center text-slate-400">Aún no hay datos de fiesta.</div>
                @endforelse
            </div>
        </div>

        <!-- TOP LISTA NEGRA (PROHIBIDAS) -->
        <div class="bg-rose-50/50 rounded-3xl p-6 border border-rose-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-rose-200">
                <span class="text-xl">🚫</span>
                <h4 class="text-sm font-bold text-rose-900">Más Vetadas en Lista Negra</h4>
            </div>
            <ul class="divide-y divide-rose-100 text-xs">
                @forelse($topBlacklist as $i => $song)
                    <li class="py-2.5 flex items-center justify-between gap-2">
                        <div class="truncate">
                            <strong class="text-rose-950">{{ $song->title }}</strong>
                            @if($song->artist) <span class="text-rose-600 block text-[11px]">{{ $song->artist }}</span> @endif
                        </div>
                        <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 font-bold text-[11px]">{{ $song->total }} vetos</span>
                    </li>
                @empty
                    <li class="py-4 text-center text-rose-400">No hay canciones vetadas.</li>
                @endforelse
            </ul>
        </div>

    </div>

    <!-- RANKING DE PERSONAL (DJS Y ASISTENTES) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- DJS -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <span class="text-xl">🎧</span>
                <h4 class="text-sm font-bold text-slate-900">Ranking de DJs (Sesiones {{ $selectedYear }})</h4>
            </div>
            <ul class="divide-y divide-slate-100 text-xs">
                @forelse($djStats as $dj)
                    <li class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-xs">
                                🎧
                            </div>
                            <div>
                                <strong class="text-slate-800">{{ $dj->name }}</strong>
                                <span class="text-slate-400 block text-[11px]">{{ $dj->email }}</span>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-xl bg-purple-50 text-purple-700 font-extrabold text-xs">
                            {{ $dj->events_as_dj_count }} eventos
                        </span>
                    </li>
                @empty
                    <li class="py-4 text-center text-slate-400">No hay DJs registrados.</li>
                @endforelse
            </ul>
        </div>

        <!-- ASISTENTES / MONTADORES -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <span class="text-xl">🛠️</span>
                <h4 class="text-sm font-bold text-slate-900">Ranking de Asistentes / Montadores ({{ $selectedYear }})</h4>
            </div>
            <ul class="divide-y divide-slate-100 text-xs">
                @forelse($assistantStats as $ast)
                    <li class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-xs">
                                🛠️
                            </div>
                            <div>
                                <strong class="text-slate-800">{{ $ast->name }}</strong>
                                <span class="text-slate-400 block text-[11px]">{{ $ast->email }}</span>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-xl bg-amber-50 text-amber-700 font-extrabold text-xs">
                            {{ $ast->events_as_assistant_count }} montajes
                        </span>
                    </li>
                @empty
                    <li class="py-4 text-center text-slate-400">No hay asistentes registrados.</li>
                @endforelse
            </ul>
        </div>

    </div>

</div>
