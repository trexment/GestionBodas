<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $companyName = \App\Models\Setting::get('company_name', 'Núñez & Son');
        $companySubtitle = \App\Models\Setting::get('company_subtitle', 'Sound in Motion');
        $companyLogo = \App\Models\Setting::get('company_logo');
        $logoUrl = ($companyLogo && \Illuminate\Support\Facades\Storage::disk('public')->exists($companyLogo))
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($companyLogo)
            : ($companyLogo ? '/storage/' . ltrim($companyLogo, '/') : null);
        $faviconUrl = $logoUrl ?: asset('favicon.svg');
    @endphp

    <title>{{ $companyName }} - Sonido, Iluminación & DJs para Eventos</title>
    
    <!-- Favicon Dinámico -->
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
    <link rel="alternate icon" href="{{ $faviconUrl }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .bg-grid-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.08) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        .glow-indigo {
            box-shadow: 0 0 50px -10px rgba(99, 102, 241, 0.4);
        }
        .glow-cyan {
            box-shadow: 0 0 50px -10px rgba(6, 182, 212, 0.4);
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white antialiased overflow-x-hidden">

    <!-- BACKGROUND GRADIENT GLOWS -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[500px] bg-gradient-to-tr from-indigo-600/25 via-purple-600/20 to-pink-500/20 blur-[130px] rounded-full"></div>
        <div class="absolute top-1/2 -right-40 w-[500px] h-[500px] bg-gradient-to-br from-cyan-500/15 to-blue-600/15 blur-[140px] rounded-full"></div>
        <div class="absolute -bottom-40 -left-40 w-[600px] h-[600px] bg-gradient-to-tr from-violet-600/20 to-indigo-700/20 blur-[150px] rounded-full"></div>
    </div>

    <div class="relative z-10 flex flex-col min-h-screen bg-grid-pattern">
        
        <!-- NAVBAR -->
        <nav class="border-b border-slate-800/80 bg-slate-950/70 backdrop-blur-xl sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    
                    <!-- BRAND LOGO -->
                    <div class="flex items-center gap-3">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="w-11 h-11 rounded-xl object-contain bg-slate-900 p-1 border border-slate-700/80 shadow-lg shadow-indigo-500/20">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                                <span class="text-xl">🎧</span>
                            </div>
                        @endif
                        <div>
                            <span class="text-xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-100 to-slate-400">
                                {{ $companyName }}
                            </span>
                            <span class="block text-[10px] uppercase font-bold tracking-widest text-indigo-400">
                                {{ $companySubtitle }}
                            </span>
                        </div>
                    </div>

                    <!-- NAV LINKS -->
                    <div class="hidden md:flex items-center space-x-8 text-sm font-medium text-slate-300">
                        <a href="#servicios" class="hover:text-white transition">Servicios</a>
                        <a href="#experiencia" class="hover:text-white transition">Por qué nosotros</a>
                        <a href="{{ route('guest.quote') }}" class="hover:text-indigo-400 transition flex items-center gap-1.5 font-semibold text-indigo-300">
                            <span>⚡</span> Calculadora Presupuesto
                        </a>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="flex items-center gap-3">
                        <a href="{{ route('guest.quote') }}" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700/80 text-xs font-bold transition shadow-sm">
                            <span>💡</span> Presupuesto Rápido
                        </a>

                        @auth
                            @if(in_array(auth()->user()->role, ['admin', 'dj', 'assistant']))
                                <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-xs font-extrabold shadow-lg shadow-indigo-500/25 transition">
                                    <span>🎧</span> {{ auth()->user()->role === 'admin' ? 'Panel de Control' : 'Mi Panel DJ / Staff' }}
                                </a>
                            @else
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold">
                                        Cerrar Sesión
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-extrabold shadow-lg shadow-indigo-600/30 transition">
                                <span>🔐</span> Acceso Staff / DJ
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>

        <!-- HERO SECTION -->
        <header class="relative pt-16 pb-24 md:pt-28 md:pb-36 text-center px-4 max-w-5xl mx-auto">
            
            <!-- BADGE -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 text-xs font-semibold mb-8 backdrop-blur-md">
                <span class="flex h-2 w-2 rounded-full bg-indigo-400 animate-pulse"></span>
                <span>Sonorización e Iluminación Profesional para Eventos de Élite</span>
            </div>

            <!-- MAIN HEADLINE -->
            <h1 class="text-4xl sm:text-6xl md:text-7xl font-extrabold tracking-tight text-white leading-tight mb-8">
                Hacemos Inolvidable la <br class="hidden sm:block">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-indigo-400 via-purple-300 to-pink-400">
                    Banda Sonora
                </span> de tu Gran Día.
            </h1>

            <p class="text-base sm:text-xl text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed font-light">
                DJs profesionales, sonido de alta fidelidad, iluminación robótica sincronizada y gestión milimétrica de tus momentos musicales más importantes.
            </p>

            <!-- CALL TO ACTIONS -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 max-w-md mx-auto">
                <a href="{{ route('guest.quote') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-extrabold text-sm shadow-xl shadow-indigo-600/30 transition transform hover:-translate-y-0.5">
                    <span>⚡</span> Calcular Presupuesto Online
                </a>
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', \App\Models\Setting::get('company_phone', '34622634790')) }}" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-slate-900/90 hover:bg-slate-800 text-slate-200 border border-slate-700/80 font-bold text-sm transition">
                    <span class="text-emerald-400 text-lg">💬</span> Hablar por WhatsApp
                </a>
            </div>

            <!-- STATS ROW -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 mt-20 pt-10 border-t border-slate-800/80">
                <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/60 backdrop-blur-sm">
                    <div class="text-3xl font-extrabold text-white mb-1">+100%</div>
                    <div class="text-xs text-slate-400 font-medium">Música Personalizada</div>
                </div>
                <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/60 backdrop-blur-sm">
                    <div class="text-3xl font-extrabold text-indigo-400 mb-1">HD</div>
                    <div class="text-xs text-slate-400 font-medium">Sonido de Alta Definición</div>
                </div>
                <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/60 backdrop-blur-sm">
                    <div class="text-3xl font-extrabold text-purple-400 mb-1">DMX</div>
                    <div class="text-xs text-slate-400 font-medium">Iluminación Espectacular</div>
                </div>
                <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/60 backdrop-blur-sm">
                    <div class="text-3xl font-extrabold text-emerald-400 mb-1">0 Estrés</div>
                    <div class="text-xs text-slate-400 font-medium">Coordinación Total</div>
                </div>
            </div>
        </header>

        <!-- SERVICES SECTION -->
        <section id="servicios" class="py-20 border-t border-slate-800/80 bg-slate-900/30 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-indigo-400">Todo lo que necesitas</span>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-white mt-2">Cobertura Integral para Cada Momento</h2>
                    <p class="text-slate-400 text-sm mt-3">Desde la emotiva entrada de la ceremonia hasta el éxtasis del último temazo en la pista de baile.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <!-- Servicio 1: Ceremonia y Cóctel -->
                    <div class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800 hover:border-indigo-500/50 transition duration-300 group hover:bg-slate-900/90 shadow-lg">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition duration-300">
                            💍
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Ceremonia & Cóctel</h3>
                        <p class="text-sm text-slate-400 leading-relaxed font-light">
                            Microfonía inalámbrica para oficiante y lecturas, sonorización impecable en exteriores e interiores, y música ambiental personalizada para recibir a tus invitados.
                        </p>
                    </div>

                    <!-- Servicio 2: Banquete y Momentos Clave -->
                    <div class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800 hover:border-purple-500/50 transition duration-300 group hover:bg-slate-900/90 shadow-lg">
                        <div class="w-14 h-14 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition duration-300">
                            🍽️
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Banquete & Sorpresas</h3>
                        <p class="text-sm text-slate-400 leading-relaxed font-light">
                            Entrada triunfal al salón, corte de tarta y coordinación musical exacta en el segundo preciso para regalos de padres, ramos de novia y momentos emotivos.
                        </p>
                    </div>

                    <!-- Servicio 3: Discomóvil & Fiesta -->
                    <div class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800 hover:border-pink-500/50 transition duration-300 group hover:bg-slate-900/90 shadow-lg">
                        <div class="w-14 h-14 rounded-2xl bg-pink-500/10 border border-pink-500/20 text-pink-400 flex items-center justify-center text-2xl mb-6 group-hover:scale-110 transition duration-300">
                            🎛️
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">DJ Baile & Barra Libre</h3>
                        <p class="text-sm text-slate-400 leading-relaxed font-light">
                            Apertura del baile nupcial, cabina de DJ espectacular, efectos robóticos, iluminación envolvente y repertorio musical seleccionado para que nadie pare de bailar.
                        </p>
                    </div>

                </div>
            </div>
        </section>

        <!-- POR QUÉ NOSOTROS / EXPERIENCIA -->
        <section id="experiencia" class="py-20 border-t border-slate-800/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-gradient-to-r from-indigo-950/60 via-purple-950/40 to-slate-900/60 border border-indigo-500/20 rounded-3xl p-8 sm:p-14 backdrop-blur-md relative overflow-hidden">
                    
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-widest text-indigo-400">Tecnología & Profesionalidad</span>
                            <h2 class="text-3xl sm:text-4xl font-extrabold text-white mt-2 leading-tight">
                                Planifica tu boda desde tu móvil con nuestra plataforma
                            </h2>
                            <p class="text-slate-300 text-sm mt-4 leading-relaxed font-light">
                                Facilitamos a nuestros clientes un cuestionario interactivo privado para configurar cada detalle: canciones que no pueden faltar, lista negra de temas prohibidos y comentarios especiales para el DJ.
                            </p>

                            <div class="mt-8 flex flex-wrap gap-4">
                                <div class="flex items-center gap-2 text-xs font-semibold text-slate-200 bg-white/5 border border-white/10 px-3.5 py-2 rounded-xl">
                                    <span>📱</span> Cuestionario Online Privado
                                </div>
                                <div class="flex items-center gap-2 text-xs font-semibold text-slate-200 bg-white/5 border border-white/10 px-3.5 py-2 rounded-xl">
                                    <span>🎵</span> Integración Apple Music & Spotify
                                </div>
                                <div class="flex items-center gap-2 text-xs font-semibold text-slate-200 bg-white/5 border border-white/10 px-3.5 py-2 rounded-xl">
                                    <span>📜</span> Contrato Legal Blindado
                                </div>
                            </div>
                        </div>

                        <div class="text-center lg:text-right">
                            <div class="inline-block p-6 rounded-2xl bg-slate-900/80 border border-slate-700/60 shadow-2xl text-left max-w-sm">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="w-12 h-12 rounded-xl bg-indigo-600/30 text-indigo-400 flex items-center justify-center text-2xl font-bold">
                                        ⚡
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-white">¿Tienes ya tu fecha?</div>
                                        <div class="text-xs text-slate-400">Comprueba presupuesto en 1 minuto</div>
                                    </div>
                                </div>
                                <a href="{{ route('guest.quote') }}" class="block text-center w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition shadow-lg shadow-indigo-600/30">
                                    Abrir Calculadora de Presupuesto
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="mt-auto border-t border-slate-900 bg-slate-950 py-10 text-xs text-slate-500">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-slate-400">
                    <span class="text-base">🎧</span>
                    <strong class="text-slate-300">{{ \App\Models\Setting::get('company_name', 'Eventos Musicales') }}</strong> &copy; {{ date('Y') }}. Todos los derechos reservados.
                </div>
                <div class="flex items-center gap-6 text-slate-400">
                    <span>📞 {{ \App\Models\Setting::get('company_phone', '+34 622 634 790') }}</span>
                    <span>📍 {{ \App\Models\Setting::get('company_city', 'Sevilla') }}</span>
                    <a href="{{ route('login') }}" class="text-indigo-400 hover:text-indigo-300 font-semibold">Acceso DJ</a>
                </div>
            </div>
        </footer>

    </div>

</body>
</html>
