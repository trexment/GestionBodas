<!DOCTYPE html>
<html lang="es" class="h-full dark">
<head>
    <meta charset="UTF-8">
    @php
        $companyName = \App\Models\Setting::getCompanyName('Núñez and Son');
        $faviconUrl = \App\Models\Setting::getFaviconUrl();
    @endphp
    <title>{{ $companyName }} - Modo Cabina DJ</title>
    
    <!-- Favicon Dinámico -->
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
    <link rel="alternate icon" href="{{ $faviconUrl }}">
    
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
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>

    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-slate-100 bg-[#080d16] selection:bg-cyan-500 selection:text-white">
    <div class="min-h-screen">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
