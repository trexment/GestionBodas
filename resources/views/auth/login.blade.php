<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $companyName = \App\Models\Setting::getCompanyName('Núñez and Son');
        $companySubtitle = \App\Models\Setting::getCompanySubtitle('Sound in Motion');
        $logoUrl = \App\Models\Setting::getLogoUrl();
        $faviconUrl = \App\Models\Setting::getFaviconUrl();
    @endphp

    <title>Acceso Staff - {{ $companyName }}</title>
    
    <!-- Favicon Dinámico -->
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
    <link rel="alternate icon" href="{{ $faviconUrl }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-grid-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.07) 1px, transparent 1px);
            background-size: 28px 28px;
        }
    </style>
</head>
<body class="h-full bg-slate-950 text-slate-100 flex items-center justify-center p-4 selection:bg-indigo-500 selection:text-white relative overflow-hidden bg-grid-pattern">

    <!-- AMBIENT GLOWS -->
    <div class="fixed inset-0 pointer-events-none z-0">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[500px] h-[500px] bg-gradient-to-tr from-indigo-600/30 via-purple-600/20 to-pink-500/20 blur-[130px] rounded-full"></div>
    </div>

    <div class="relative z-10 w-full max-w-md">
        
        <!-- BRAND BADGE -->
        <div class="text-center mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 group flex-col">
                @if($logoUrl)
                    <img src="{{ $logoUrl }}" alt="{{ $companyName }}" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';" class="w-16 h-16 rounded-2xl object-contain bg-slate-900 p-1.5 border border-slate-700/80 shadow-2xl shadow-indigo-500/30 group-hover:scale-105 transition mx-auto">
                    <div style="display: none;" class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 items-center justify-center shadow-xl shadow-indigo-500/30 group-hover:scale-105 transition mx-auto">
                        <span class="text-3xl">🎧</span>
                    </div>
                @else
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center shadow-xl shadow-indigo-500/30 group-hover:scale-105 transition mx-auto">
                        <span class="text-3xl">🎧</span>
                    </div>
                @endif
            </a>
            <h1 class="text-2xl font-extrabold text-white mt-4 tracking-tight">
                {{ $companyName }}
            </h1>
            <p class="text-xs text-slate-400 mt-1 uppercase tracking-widest font-bold text-indigo-400">
                Portal de Administración y DJs
            </p>
        </div>

        <!-- LOGIN CARD -->
        <div class="bg-slate-900/80 backdrop-blur-xl border border-slate-800 rounded-3xl p-8 shadow-2xl">
            
            @if ($errors->any())
                <div class="bg-rose-500/10 border border-rose-500/30 text-rose-400 px-4 py-3 rounded-xl mb-6 text-xs flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2" for="login">
                        Usuario o Correo Electrónico
                    </label>
                    <div class="relative">
                        <input class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" id="login" type="text" name="login" value="{{ old('login') }}" required autofocus placeholder="ej. Fran o admin@email.com">
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2" for="password">
                        Contraseña
                    </label>
                    <div class="relative">
                        <input class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" id="password" type="password" name="password" required placeholder="••••••••">
                    </div>
                </div>
                
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-950 border-slate-700 text-indigo-600 focus:ring-indigo-500 mr-2">
                        <span>Recordar sesión</span>
                    </label>
                </div>

                <div class="pt-2">
                    <button class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-extrabold text-sm shadow-lg shadow-indigo-600/30 transition transform hover:-translate-y-0.5 cursor-pointer" type="submit">
                        Iniciar Sesión
                    </button>
                </div>
            </form>

            <div class="mt-6 pt-6 border-t border-slate-800 text-center">
                <a href="{{ route('home') }}" class="text-xs text-slate-400 hover:text-indigo-400 transition inline-flex items-center gap-1 font-medium">
                    <span>&larr;</span> Volver a la web pública
                </a>
            </div>
        </div>

    </div>

</body>
</html>
