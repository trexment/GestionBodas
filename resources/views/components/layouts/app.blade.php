<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $companyName = \App\Models\Setting::get('company_name', 'Núñez and Son');
        $companySubtitle = \App\Models\Setting::get('company_subtitle', 'Sound in Motion');
        $companyLogo = \App\Models\Setting::get('company_logo');
        $logoUrl = ($companyLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyLogo))
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($companyLogo)
            : ($companyLogo ? '/storage/' . ltrim($companyLogo, '/') : null);
        $faviconUrl = $logoUrl ?: asset('favicon.svg');
    @endphp

    <title>{{ $title ?? $companyName . ' - Panel de Gestión' }}</title>
    
    <!-- Favicon Dinámico -->
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
    <link rel="alternate icon" href="{{ $faviconUrl }}">
    
    <!-- Tailwind CSS CDN + Configuración Dark Mode -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Script Anti-Flicker para aplicar el tema de forma instantánea antes de renderizar -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        [x-cloak] { display: none !important; }
        
        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        /* ========================================================= */
        /* ADAPTACIÓN COMPLETA DE MODO OSCURO (CARTELAS, TABLAS, ETC) */
        /* ========================================================= */
        html.dark {
            color-scheme: dark;
        }

        html.dark body {
            background-color: #030712 !important; /* ultra dark slate-950 */
            color: #f3f4f6 !important;
        }

        /* Cartelas / Cards / Paneles blancos */
        html.dark .bg-white {
            background-color: #0f172a !important; /* slate-900 */
            color: #f8fafc !important;
            border-color: #1e293b !important;
        }

        html.dark .bg-slate-50,
        html.dark .bg-gray-50 {
            background-color: #090d16 !important;
            color: #cbd5e1 !important;
        }

        html.dark .bg-slate-100,
        html.dark .bg-gray-100 {
            background-color: #1e293b !important; /* slate-800 */
            color: #e2e8f0 !important;
        }

        html.dark .bg-slate-200,
        html.dark .bg-gray-200 {
            background-color: #334155 !important; /* slate-700 */
            color: #f8fafc !important;
        }

        /* Píldoras y Badges de Filtros */
        html.dark .bg-amber-50 {
            background-color: rgba(245, 158, 11, 0.15) !important;
            color: #fbbf24 !important;
        }
        html.dark .bg-blue-50 {
            background-color: rgba(59, 130, 246, 0.15) !important;
            color: #93c5fd !important;
        }
        html.dark .bg-purple-50 {
            background-color: rgba(168, 85, 247, 0.15) !important;
            color: #d8b4fe !important;
        }
        html.dark .bg-emerald-50,
        html.dark .bg-green-50 {
            background-color: rgba(16, 185, 129, 0.15) !important;
            color: #6ee7b7 !important;
        }
        html.dark .bg-rose-50,
        html.dark .bg-red-50 {
            background-color: rgba(244, 63, 94, 0.15) !important;
            color: #fda4af !important;
        }

        html.dark .bg-amber-100 {
            background-color: rgba(245, 158, 11, 0.22) !important;
            color: #fde68a !important;
        }
        html.dark .bg-blue-100 {
            background-color: rgba(59, 130, 246, 0.22) !important;
            color: #bfdbfe !important;
        }
        html.dark .bg-purple-100 {
            background-color: rgba(168, 85, 247, 0.22) !important;
            color: #e9d5ff !important;
        }
        html.dark .bg-emerald-100,
        html.dark .bg-green-100 {
            background-color: rgba(16, 185, 129, 0.22) !important;
            color: #a7f3d0 !important;
        }
        html.dark .bg-rose-100,
        html.dark .bg-red-100 {
            background-color: rgba(244, 63, 94, 0.22) !important;
            color: #fecdd3 !important;
        }

        /* Bordes y Divisores de Cartelas */
        html.dark .border-gray-100,
        html.dark .border-gray-200,
        html.dark .border-gray-300,
        html.dark .border-slate-100,
        html.dark .border-slate-200,
        html.dark .border-slate-300 {
            border-color: #1e293b !important;
        }

        html.dark .divide-gray-100 > :not([hidden]) ~ :not([hidden]),
        html.dark .divide-gray-200 > :not([hidden]) ~ :not([hidden]),
        html.dark .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
        html.dark .divide-slate-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: #1e293b !important;
        }

        /* Textos de alto contraste */
        html.dark .text-gray-900,
        html.dark .text-slate-900,
        html.dark .text-gray-800,
        html.dark .text-slate-800 {
            color: #f8fafc !important;
        }

        html.dark .text-gray-700,
        html.dark .text-slate-700,
        html.dark .text-gray-600,
        html.dark .text-slate-600 {
            color: #cbd5e1 !important;
        }

        html.dark .text-gray-500,
        html.dark .text-slate-500,
        html.dark .text-gray-400,
        html.dark .text-slate-400 {
            color: #94a3b8 !important;
        }

        html.dark .text-indigo-700,
        html.dark .text-indigo-800 {
            color: #818cf8 !important;
        }
        html.dark .text-amber-700,
        html.dark .text-amber-800 {
            color: #fcd34d !important;
        }
        html.dark .text-blue-700,
        html.dark .text-blue-800 {
            color: #93c5fd !important;
        }
        html.dark .text-emerald-700,
        html.dark .text-emerald-800 {
            color: #6ee7b7 !important;
        }
        html.dark .text-purple-700,
        html.dark .text-purple-800 {
            color: #d8b4fe !important;
        }

        /* Tablas y Encabezados de Cartelas */
        html.dark table thead th,
        html.dark table th {
            background-color: #111827 !important; /* Dark table header */
            color: #94a3b8 !important;
            border-color: #1e293b !important;
        }

        html.dark table tbody tr {
            border-color: #1e293b !important;
        }

        html.dark table tbody tr:hover {
            background-color: #131d31 !important;
        }

        html.dark td {
            border-color: #1e293b !important;
        }

        /* Formularios, Inputs, Selects y Textareas */
        html.dark input[type="text"],
        html.dark input[type="email"],
        html.dark input[type="password"],
        html.dark input[type="number"],
        html.dark input[type="date"],
        html.dark input[type="time"],
        html.dark input[type="datetime-local"],
        html.dark select,
        html.dark textarea {
            background-color: #1e293b !important;
            border-color: #334155 !important;
            color: #f8fafc !important;
        }

        html.dark input::placeholder,
        html.dark textarea::placeholder {
            color: #64748b !important;
        }

        html.dark input:focus,
        html.dark select:focus,
        html.dark textarea:focus {
            border-color: #6366f1 !important;
            outline: none !important;
        }

        /* Sombras de Cartelas para profundidad en modo oscuro */
        html.dark .shadow,
        html.dark .shadow-sm,
        html.dark .shadow-md,
        html.dark .shadow-lg,
        html.dark .shadow-xl {
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05) !important;
        }

        /* Modales */
        html.dark .bg-gray-500.bg-opacity-75,
        html.dark .bg-slate-900\/50 {
            background-color: rgba(2, 6, 23, 0.85) !important;
        }
    </style>
    @livewireStyles
</head>
<body 
    class="h-full font-sans antialiased text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-950 selection:bg-indigo-500 selection:text-white transition-colors duration-200"
    x-data="{
        mobileSidebarOpen: false,
        isDark: localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches),
        toggleTheme() {
            this.isDark = !this.isDark;
            if (this.isDark) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('theme', 'light');
            }
        }
    }"
>

    <div class="min-h-full flex">
        
        <!-- MOBILE BACKDROP -->
        <div 
            x-show="mobileSidebarOpen" 
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-slate-950/80 backdrop-blur-xs z-50 lg:hidden"
            @click="mobileSidebarOpen = false"
            style="display: none;"
        ></div>

        <!-- ============================================== -->
        <!-- MENÚ LATERAL IZQUIERDO (SIDEBAR)              -->
        <!-- ============================================== -->
        <aside 
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800/80 flex flex-col justify-between transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0 shadow-xl lg:shadow-none"
            :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- TOP BRAND / LOGO -->
            <div class="p-5 border-b border-slate-800/80">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 group">
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="w-10 h-10 rounded-xl object-contain bg-slate-950 p-1 border border-slate-700/80 shadow-lg shadow-indigo-500/10 group-hover:scale-105 transition">
                    @else
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-500 via-purple-600 to-pink-500 flex items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition">
                            <span class="text-lg">🎧</span>
                        </div>
                    @endif
                    <div class="truncate">
                        <span class="text-sm font-extrabold text-white tracking-tight block leading-tight truncate">
                            {{ $companyName }}
                        </span>
                        <span class="text-[10px] text-indigo-400 font-bold uppercase tracking-wider block">
                            {{ $companySubtitle }}
                        </span>
                    </div>
                </a>
            </div>

            <!-- NAVEGACIÓN PRINCIPAL (SCROLLABLE) -->
            <div class="flex-1 overflow-y-auto sidebar-scroll py-4 px-3 space-y-1">
                
                <div class="px-3 pb-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                    Menú Principal
                </div>

                <!-- 1. DASHBOARD -->
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <span class="text-base">📊</span>
                    <span>Panel de Control</span>
                </a>

                <!-- 2. EVENTOS -->
                <a href="{{ route('admin.events') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.events') || request()->routeIs('admin.events.show') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <span class="text-base">🎉</span>
                    <span>Gestión de Eventos</span>
                </a>

                <!-- 3. CALENDARIO -->
                <a href="{{ route('admin.calendar') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.calendar') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <span class="text-base">📅</span>
                    <span>Calendario y Actuaciones</span>
                </a>

                @if(Auth::check() && Auth::user()->role === 'admin')
                <!-- 4. PERSONAL & DJS -->
                <a href="{{ route('admin.users') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.users') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <span class="text-base">👥</span>
                    <span>Personal, DJs & Clientes</span>
                </a>
                @endif

                <!-- 5. REPERTORIO MUSICAL -->
                <a href="{{ route('admin.music') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.music') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <span class="text-base">🎵</span>
                    <span>Biblioteca Musical</span>
                </a>

                @if(Auth::check() && Auth::user()->role === 'admin')
                <!-- 6. ESTADÍSTICAS & TENDENCIAS -->
                <a href="{{ route('admin.analytics') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.analytics') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <span class="text-base">📈</span>
                    <span>Estadísticas Temporada</span>
                </a>
                @endif

                <!-- 7. INVENTARIO & MATERIAL -->
                <a href="{{ route('admin.inventory') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.inventory') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <span class="text-base">📦</span>
                    <span>Inventario & Equipos</span>
                </a>

                @if(Auth::check() && Auth::user()->role === 'admin')
                <!-- 8. CONFIGURACIÓN & INTEGRACIONES -->
                <a href="{{ route('admin.settings') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.settings') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <span class="text-base">⚙️</span>
                    <span>Configuración & Streaming</span>
                </a>
                @endif

                <!-- SECCIÓN ACCESOS EXTERNOS -->
                <div class="pt-5 px-3 pb-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">
                    Enlaces Directos
                </div>

                <a href="{{ route('home') }}" target="_blank" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                    <span class="flex items-center gap-2.5">
                        <span>🌐</span>
                        <span>Web Pública</span>
                    </span>
                    <span class="text-slate-400 text-[10px]">&nearr;</span>
                </a>

                <a href="{{ route('guest.quote') }}" target="_blank" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
                    <span class="flex items-center gap-2.5">
                        <span>💶</span>
                        <span>Cotizador Online</span>
                    </span>
                    <span class="text-slate-400 text-[10px]">&nearr;</span>
                </a>

            </div>

            <!-- FOOTER SIDEBAR: TEMA & PERFIL DE USUARIO & LOGOUT -->
            <div class="p-4 border-t border-slate-800/80 bg-slate-950/40 space-y-3">
                
                <!-- SELECTOR DE TEMA EN SIDEBAR -->
                <div class="px-3 py-2 bg-slate-900/90 rounded-xl border border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs font-bold text-slate-300">
                        <span x-show="!isDark" class="flex items-center gap-1.5 text-amber-400">
                            <span>☀️</span> Modo Claro
                        </span>
                        <span x-show="isDark" class="flex items-center gap-1.5 text-indigo-400" style="display: none;">
                            <span>🌙</span> Modo Oscuro
                        </span>
                    </div>
                    <button 
                        type="button" 
                        @click="toggleTheme()"
                        class="w-11 h-6 rounded-full p-0.5 transition duration-300 flex items-center"
                        :class="isDark ? 'bg-indigo-600 justify-end' : 'bg-slate-700 justify-start'"
                        title="Alternar Modo Oscuro / Claro"
                    >
                        <div class="w-5 h-5 rounded-full bg-white shadow-md transform transition-transform"></div>
                    </button>
                </div>

                <div class="flex items-center justify-between gap-3 pt-1">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 text-white flex items-center justify-center font-black text-xs shrink-0 shadow-sm">
                            {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                        </div>
                        <div class="truncate">
                            <span class="block text-xs font-bold text-white truncate leading-tight">
                                {{ Auth::user()->name ?? 'Usuario' }}
                            </span>
                            <span class="block text-[10px] text-indigo-400 font-semibold uppercase truncate">
                                {{ Auth::user()->role ?? 'Admin' }}
                            </span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Cerrar sesión" class="p-2 rounded-xl bg-slate-800/80 hover:bg-rose-950/80 text-slate-400 hover:text-rose-300 border border-slate-700/60 transition flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- ============================================== -->
        <!-- CONTENIDO PRINCIPAL (DERECHA)                  -->
        <!-- ============================================== -->
        <div class="flex-1 flex flex-col min-w-0 bg-slate-50 dark:bg-slate-950 min-h-screen transition-colors duration-200">
            
            <!-- TOP BAR HEADER (MOBILE TOGGLE & ACCIONES RÁPIDAS) -->
            <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 shadow-2xs transition-colors duration-200">
                <div class="px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16">
                    
                    <!-- BOTÓN HAMBURGUESA MÓVIL & BREADCRUMB -->
                    <div class="flex items-center gap-3">
                        <button 
                            type="button" 
                            @click="mobileSidebarOpen = true"
                            class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 lg:hidden transition"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>

                        <div class="hidden sm:flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <span>Panel</span>
                            <span>/</span>
                            <span class="text-slate-900 dark:text-white font-bold">{{ $header ?? 'Gestión de Eventos' }}</span>
                        </div>
                    </div>

                    <!-- ACCIONES SUPERIORES DERECHA -->
                    <div class="flex items-center gap-2.5 sm:gap-3">
                        
                        <!-- TOGGLE TEMA OSCURO / CLARO EN HEADER -->
                        <button 
                            type="button"
                            @click="toggleTheme()"
                            title="Cambiar Modo Claro/Oscuro"
                            class="p-2 sm:px-3 sm:py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 font-bold text-xs flex items-center gap-2 transition"
                        >
                            <span x-show="!isDark" class="flex items-center gap-1.5 text-amber-600 font-semibold">
                                <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path></svg>
                                <span class="hidden md:inline text-slate-700">Modo Claro</span>
                            </span>
                            <span x-show="isDark" class="flex items-center gap-1.5 text-indigo-400 font-semibold" style="display: none;">
                                <svg class="w-4 h-4 text-indigo-400" fill="currentColor" viewBox="0 0 20 20"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                                <span class="hidden md:inline text-slate-200">Modo Oscuro</span>
                            </span>
                        </button>

                        @if(Auth::check() && Auth::user()->role === 'admin')
                        <a href="{{ route('admin.events') }}" class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm shadow-indigo-600/30 transition flex items-center gap-1.5">
                            <span>➕</span> <span class="hidden sm:inline">Nuevo Evento</span>
                        </a>
                        <a href="{{ route('admin.settings') }}" title="Configuración" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700/80 transition">
                            <span>⚙️</span>
                        </a>
                        @endif
                    </div>

                </div>
            </header>

            <!-- MAIN VIEW CONTAINER -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                {{ $slot }}
            </main>

            <!-- FOOTER -->
            <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 py-3 text-center text-xs text-slate-400 dark:text-slate-500 mt-auto transition-colors duration-200">
                <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('company_name', 'Núñez and Son') }} &bull; Software Integral de Gestión para DJs y Eventos</p>
            </footer>

        </div>

    </div>

    @livewireScripts
</body>
</html>
