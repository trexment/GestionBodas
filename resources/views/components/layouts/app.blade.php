<!DOCTYPE html>
<html lang="es" class="min-h-full h-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    @php
        $companyName = \App\Models\Setting::getCompanyName('Núñez and Son');
        $companySubtitle = \App\Models\Setting::getCompanySubtitle('Sound in Motion');
        $logoUrl = \App\Models\Setting::getLogoUrl();
        $faviconUrl = \App\Models\Setting::getFaviconUrl();
    @endphp

    <title>{{ $title ?? $companyName . ' - Panel de Gestión' }}</title>
    
    <!-- Favicon Dinámico & PWA Android -->
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
    <link rel="alternate icon" href="{{ $faviconUrl }}">
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#080d16">
    
    <!-- Tailwind CSS CDN + Configuración Dark Mode -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    spacing: {
                        18: '4.5rem'
                    },
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
        html, body {
            -webkit-overflow-scrolling: touch;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            overflow-x: hidden;
        }
        [x-cloak] { display: none !important; }
        
        /* Utility to hide scrollbar while keeping scroll functionality */
        .scrollbar-none {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .scrollbar-none::-webkit-scrollbar {
            display: none;
        }

        /* Custom scrollbar for sidebar and global elements */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background-color: rgba(148, 163, 184, 0.25);
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background-color: rgba(148, 163, 184, 0.45);
        }
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
        html.dark .bg-gray-50,
        html.dark [class*="bg-slate-50/"],
        html.dark [class*="bg-gray-50/"] {
            background-color: #090d16 !important;
            color: #cbd5e1 !important;
        }

        html.dark .bg-slate-100,
        html.dark .bg-gray-100,
        html.dark [class*="bg-slate-100/"],
        html.dark [class*="bg-gray-100/"] {
            background-color: #0f172a !important; /* slate-900 */
            color: #e2e8f0 !important;
        }

        html.dark .bg-slate-200,
        html.dark .bg-gray-200,
        html.dark [class*="bg-slate-200/"],
        html.dark [class*="bg-gray-200/"] {
            background-color: #1e293b !important; /* slate-800 */
            color: #f8fafc !important;
        }

        html.dark [class*="from-slate-50"],
        html.dark [class*="from-indigo-50"],
        html.dark [class*="from-emerald-50"] {
            --tw-gradient-from: rgba(30, 41, 59, 0.65) var(--tw-gradient-from-position) !important;
            --tw-gradient-to: rgba(15, 23, 42, 0.85) var(--tw-gradient-to-position) !important;
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
        }

        /* Píldoras, Fondos suaves y Badges */
        html.dark .bg-indigo-50,
        html.dark [class*="bg-indigo-50/"] {
            background-color: rgba(99, 102, 241, 0.14) !important;
            color: #c7d2fe !important;
        }
        html.dark .bg-amber-50,
        html.dark [class*="bg-amber-50/"] {
            background-color: rgba(245, 158, 11, 0.14) !important;
            color: #fbbf24 !important;
        }
        html.dark .bg-blue-50,
        html.dark [class*="bg-blue-50/"] {
            background-color: rgba(59, 130, 246, 0.14) !important;
            color: #93c5fd !important;
        }
        html.dark .bg-purple-50,
        html.dark [class*="bg-purple-50/"] {
            background-color: rgba(168, 85, 247, 0.14) !important;
            color: #d8b4fe !important;
        }
        html.dark .bg-emerald-50,
        html.dark .bg-green-50,
        html.dark [class*="bg-emerald-50/"],
        html.dark [class*="bg-teal-50/"] {
            background-color: rgba(16, 185, 129, 0.14) !important;
            color: #6ee7b7 !important;
        }
        html.dark .bg-pink-50,
        html.dark [class*="bg-pink-50/"],
        html.dark .bg-rose-50,
        html.dark .bg-red-50 {
            background-color: rgba(244, 63, 94, 0.14) !important;
            color: #fda4af !important;
        }
        html.dark .bg-orange-50,
        html.dark [class*="bg-orange-50/"] {
            background-color: rgba(249, 115, 22, 0.14) !important;
            color: #fdba74 !important;
        }

        html.dark .bg-indigo-100 {
            background-color: rgba(99, 102, 241, 0.22) !important;
            color: #e0e7ff !important;
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
        html.dark .bg-pink-100,
        html.dark .bg-rose-100,
        html.dark .bg-red-100 {
            background-color: rgba(244, 63, 94, 0.22) !important;
            color: #fecdd3 !important;
        }
        html.dark .bg-orange-100 {
            background-color: rgba(249, 115, 22, 0.22) !important;
            color: #fed7aa !important;
        }

        /* Bordes y Divisores de Cartelas */
        html.dark .border-gray-100,
        html.dark .border-gray-200,
        html.dark .border-gray-300,
        html.dark .border-slate-100,
        html.dark .border-slate-200,
        html.dark .border-slate-300,
        html.dark .border-indigo-100,
        html.dark .border-indigo-200,
        html.dark .border-purple-100,
        html.dark .border-amber-100,
        html.dark .border-amber-200,
        html.dark .border-pink-100,
        html.dark .border-pink-200,
        html.dark .border-orange-100,
        html.dark .border-orange-200,
        html.dark [class*="border-gray-200/"],
        html.dark [class*="border-slate-200/"],
        html.dark [class*="border-indigo-200/"],
        html.dark [class*="border-emerald-200/"] {
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
        html.dark .text-indigo-800,
        html.dark .text-indigo-900,
        html.dark .text-indigo-950 {
            color: #a5b4fc !important;
        }
        html.dark .text-amber-700,
        html.dark .text-amber-800,
        html.dark .text-amber-900,
        html.dark .text-amber-950 {
            color: #fcd34d !important;
        }
        html.dark .text-blue-700,
        html.dark .text-blue-800,
        html.dark .text-blue-900 {
            color: #93c5fd !important;
        }
        html.dark .text-emerald-700,
        html.dark .text-emerald-800,
        html.dark .text-emerald-900,
        html.dark .text-emerald-950 {
            color: #6ee7b7 !important;
        }
        html.dark .text-purple-700,
        html.dark .text-purple-800,
        html.dark .text-purple-900,
        html.dark .text-purple-950 {
            color: #d8b4fe !important;
        }
        html.dark .text-pink-700,
        html.dark .text-pink-800,
        html.dark .text-pink-900 {
            color: #f9a8d4 !important;
        }
        html.dark .text-orange-700,
        html.dark .text-orange-800,
        html.dark .text-orange-900 {
            color: #fdba74 !important;
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
    @php
        $initialNotifData = Auth::check()
            ? \App\Services\AppNotificationService::getForUser((int) Auth::id(), 20)
            : ['unread_count' => 0, 'notifications' => [], 'latest_timestamp' => 0];
    @endphp
</head>
<body 
    class="min-h-screen h-auto font-sans antialiased text-slate-800 dark:text-slate-100 bg-slate-50 dark:bg-slate-950 selection:bg-indigo-500 selection:text-white transition-colors duration-200 overflow-x-hidden"
    x-data="{
        mobileSidebarOpen: false,
        notifOpen: false,
        unreadCount: {{ (int) ($initialNotifData['unread_count'] ?? 0) }},
        notifications: @js($initialNotifData['notifications'] ?? []),
        lastSeenTimestamp: {{ (int) ($initialNotifData['latest_timestamp'] ?? 0) }},
        pollCounter: 0,
        notifPermission: ('Notification' in window) ? Notification.permission : 'unsupported',
        toasts: [],
        addToast(message, type = 'success', duration = 4500) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.removeToast(id);
            }, duration);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
        playNotificationSound() {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const now = ctx.currentTime;
                const osc1 = ctx.createOscillator();
                const osc2 = ctx.createOscillator();
                const gain = ctx.createGain();
                osc1.type = 'sine';
                osc2.type = 'triangle';
                osc1.frequency.setValueAtTime(587.33, now); // D5
                osc1.frequency.setValueAtTime(880, now + 0.12); // A5
                osc2.frequency.setValueAtTime(293.66, now);
                osc2.frequency.setValueAtTime(440, now + 0.12);
                gain.gain.setValueAtTime(0.18, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
                osc1.connect(gain);
                osc2.connect(gain);
                gain.connect(ctx.destination);
                osc1.start(now);
                osc2.start(now);
                osc1.stop(now + 0.45);
                osc2.stop(now + 0.45);
            } catch (e) {}
        },
        requestNotificationPermission() {
            if (!('Notification' in window)) return;
            Notification.requestPermission().then(perm => {
                this.notifPermission = perm;
                if (perm === 'granted') {
                    this.addToast('🔔 Avisos del navegador y móvil activados correctamente', 'success');
                    this.playNotificationSound();
                }
            });
        },
        triggerBrowserNotification(notif) {
            if (!('Notification' in window) || Notification.permission !== 'granted') return;
            const title = notif.title || 'Nuevo aviso en Eventos Musicales';
            const options = {
                body: notif.body || '',
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                vibrate: [200, 100, 200],
                data: { url: notif.url || '/admin/events' },
                tag: notif.id || ('notif-' + Date.now())
            };
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistration().then(reg => {
                    if (reg && reg.showNotification) {
                        reg.showNotification(title, options);
                    } else {
                        const n = new Notification(title, options);
                        n.onclick = () => { window.focus(); window.location.href = options.data.url; };
                    }
                }).catch(() => {
                    const n = new Notification(title, options);
                    n.onclick = () => { window.focus(); window.location.href = options.data.url; };
                });
            } else {
                const n = new Notification(title, options);
                n.onclick = () => { window.focus(); window.location.href = options.data.url; };
            }
        },
        pollNotifications() {
            this.pollCounter++;
            const checkImap = (this.pollCounter % 8 === 0) ? '?check_imap=1' : '';
            fetch('/admin/notifications/poll' + checkImap, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data || !Array.isArray(data.notifications)) return;
                const prevTs = this.lastSeenTimestamp || 0;
                const newItems = data.notifications.filter(n => (n.timestamp || 0) > prevTs && n.unread);
                this.unreadCount = data.unread_count || 0;
                this.notifications = data.notifications;
                if (data.latest_timestamp && data.latest_timestamp > this.lastSeenTimestamp) {
                    this.lastSeenTimestamp = data.latest_timestamp;
                }
                if (newItems.length > 0 && prevTs > 0) {
                    this.playNotificationSound();
                    newItems.forEach(item => {
                        this.addToast((item.title || 'Nuevo aviso') + ' — ' + (item.body || ''), 'info', 7000);
                        this.triggerBrowserNotification(item);
                    });
                } else if (prevTs === 0 && data.latest_timestamp) {
                    this.lastSeenTimestamp = data.latest_timestamp;
                }
            })
            .catch(() => {});
        },
        markAllNotificationsRead() {
            fetch('/admin/notifications/read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                },
                body: JSON.stringify({ all: true })
            })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (data) {
                    this.unreadCount = data.unread_count || 0;
                    this.notifications = data.notifications || [];
                }
            });
        },
        clearAllNotifications() {
            fetch('/admin/notifications/read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                },
                body: JSON.stringify({ clear_all: true })
            })
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (data) {
                    this.unreadCount = 0;
                    this.notifications = [];
                }
            });
        },
        openNotification(notif) {
            fetch('/admin/notifications/read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                },
                body: JSON.stringify({ id: notif.id })
            }).finally(() => {
                window.location.href = notif.url || '/admin/events';
            });
        },
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
    x-init="
        @if(session()->has('message'))
            addToast('{{ addslashes(session('message')) }}', 'success');
        @endif
        @if(session()->has('success'))
            addToast('{{ addslashes(session('success')) }}', 'success');
        @endif
        @if(session()->has('error'))
            addToast('{{ addslashes(session('error')) }}', 'error');
        @endif
        window.addEventListener('notify', e => {
            const msg = typeof e.detail === 'string' ? e.detail : (e.detail.message || e.detail[0] || 'Operación realizada correctamente');
            const type = e.detail.type || 'success';
            addToast(msg, type);
        });
        @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'dj']))
            setInterval(() => { pollNotifications(); }, 15000);
        @endif
    "
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
                        <img src="{{ $logoUrl }}" alt="{{ $companyName }}" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';" class="w-10 h-10 rounded-xl object-contain bg-slate-950 p-1 border border-slate-700/80 shadow-lg shadow-indigo-500/10 group-hover:scale-105 transition">
                        <div style="display: none;" class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-indigo-500 via-purple-600 to-pink-500 items-center justify-center shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition">
                            <span class="text-lg">🎧</span>
                        </div>
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
                <!-- 4. CLIENTES -->
                <a href="{{ route('admin.clients') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.clients') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <span class="text-base">👤</span>
                    <span>Clientes y Novios</span>
                </a>

                <!-- 5. EQUIPO Y PERSONAL (DJs, Asistentes, Admins) -->
                <a href="{{ route('admin.users') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition {{ request()->routeIs('admin.users') ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-800/80' }}">
                    <span class="text-base">👥</span>
                    <span>Equipo y Personal</span>
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

                <a href="{{ \App\Models\Setting::getPublicWebsiteUrl() }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/60 transition">
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

                <a href="{{ route('manual') }}" target="_blank" class="flex items-center justify-between px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-800/60 transition" title="Manual Oficial de Uso con capturas y guía paso a paso">
                    <span class="flex items-center gap-2.5">
                        <span>📖</span>
                        <span>Manual de Uso</span>
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
                    <div class="flex items-center gap-2 sm:gap-2.5">
                        
                        <!-- CAMPANA DE NOTIFICACIONES EN TIEMPO REAL (WHATSAPP Y EMAILS) -->
                        @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'dj']))
                        <div class="relative" @click.away="notifOpen = false">
                            <button 
                                type="button"
                                @click="notifOpen = !notifOpen"
                                title="Avisos de WhatsApp y Emails recibidos"
                                class="relative p-2 sm:px-2.5 sm:py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700/80 font-bold text-xs flex items-center gap-1.5 transition cursor-pointer"
                            >
                                <span class="text-base leading-none">🔔</span>
                                <span 
                                    x-show="unreadCount > 0" 
                                    x-text="unreadCount > 9 ? '9+' : unreadCount"
                                    style="display: none;"
                                    class="px-1.5 py-0.5 text-[10px] font-extrabold bg-rose-600 text-white rounded-full leading-none shadow-sm animate-pulse"
                                ></span>
                            </button>

                            <!-- DROPDOWN PANEL DE NOTIFICACIONES -->
                            <div 
                                x-show="notifOpen"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                style="display: none;"
                                class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-2xl z-50 overflow-hidden"
                            >
                                <div class="px-4 py-3 bg-slate-50 dark:bg-slate-800/90 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2">
                                    <div>
                                        <h4 class="text-xs font-extrabold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-1.5">
                                            <span>🔔</span> Avisos de Novios y Leads
                                        </h4>
                                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Solo clientes con evento activo y solicitudes web/email</p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <button 
                                            x-show="unreadCount > 0"
                                            type="button"
                                            @click="markAllNotificationsRead()"
                                            class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
                                        >
                                            Leídas
                                        </button>
                                        <button 
                                            x-show="notifications.length > 0"
                                            type="button"
                                            @click="clearAllNotifications()"
                                            class="text-[11px] font-bold text-rose-500 hover:text-rose-400 hover:underline cursor-pointer"
                                            title="Vaciar lista de avisos"
                                        >
                                            Vaciar
                                        </button>
                                    </div>
                                </div>

                                <!-- BOTÓN ACTIVAR NOTIFICACIONES NATIVAS EN MÓVIL / NAVEGADOR -->
                                <div x-show="notifPermission === 'default'" class="px-4 py-2.5 bg-indigo-50 dark:bg-indigo-950/60 border-b border-indigo-100 dark:border-indigo-900/60 flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-semibold text-indigo-800 dark:text-indigo-200 leading-tight">
                                        📲 Activa las notificaciones push con sonido en este dispositivo
                                    </span>
                                    <button 
                                        type="button"
                                        @click="requestNotificationPermission()"
                                        class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold shrink-0 cursor-pointer transition"
                                    >
                                        Activar
                                    </button>
                                </div>

                                <!-- LISTA DE NOTIFICACIONES -->
                                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-if="notifications.length === 0">
                                        <div class="p-6 text-center text-xs text-slate-400 dark:text-slate-500">
                                            No hay notificaciones recientes de WhatsApp ni Emails.
                                        </div>
                                    </template>
                                    <template x-for="item in notifications" :key="item.id">
                                        <button 
                                            type="button"
                                            @click="openNotification(item)"
                                            class="w-full text-left px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition flex items-start gap-3 cursor-pointer"
                                            :class="item.unread ? 'bg-indigo-50/50 dark:bg-indigo-950/30' : ''"
                                        >
                                            <div 
                                                class="w-8 h-8 rounded-xl flex items-center justify-center text-sm shrink-0 mt-0.5"
                                                :class="{
                                                    'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300': item.type === 'whatsapp_inbound',
                                                    'bg-blue-100 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300': item.type === 'email_lead',
                                                    'bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300': item.type === 'quote_request'
                                                }"
                                            >
                                                <span x-text="item.type === 'whatsapp_inbound' ? '💬' : (item.type === 'quote_request' ? '💶' : '📩')"></span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-xs font-extrabold text-slate-900 dark:text-white truncate" x-text="item.title"></span>
                                                    <span class="text-[10px] text-slate-400 shrink-0" x-text="item.created_at"></span>
                                                </div>
                                                <p class="text-[11px] text-slate-600 dark:text-slate-300 line-clamp-2 mt-0.5 leading-snug" x-text="item.body"></p>
                                            </div>
                                            <span x-show="item.unread" class="w-2 h-2 rounded-full bg-rose-500 shrink-0 mt-2"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- BOTÓN INSTALAR APP / GUÍA MÓVIL & TABLET -->
                        <button 
                            type="button"
                            @click="$dispatch('open-pwa-guide')"
                            title="Instalar App en Móvil o Tablet / Guía de Instalación"
                            class="px-2.5 sm:px-3 py-1.5 rounded-xl bg-purple-50 dark:bg-purple-950/60 hover:bg-purple-100 dark:hover:bg-purple-900/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 font-bold text-xs flex items-center gap-1.5 transition cursor-pointer shadow-2xs"
                        >
                            <span>📲</span>
                            <span class="hidden sm:inline">Instalar App</span>
                        </button>

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
            <main class="flex-1 min-w-0 p-3 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                {{ $slot }}
            </main>

            <!-- FOOTER -->
            <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 py-3 text-center text-xs text-slate-400 dark:text-slate-500 mt-auto transition-colors duration-200">
                <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('company_name', 'Núñez and Son') }} &bull; Software Integral de Gestión para DJs y Eventos &bull; <button type="button" @click="$dispatch('open-pwa-guide')" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold inline-flex items-center gap-1">📲 Instalar App</button></p>
            </footer>

        </div>

    </div>

    <!-- FLOATING TOAST NOTIFICATIONS (MOBILE & DESKTOP) -->
    <div class="fixed top-4 right-4 sm:top-6 sm:right-6 z-50 flex flex-col gap-2.5 max-w-sm w-full pointer-events-none px-3 sm:px-0">
        <template x-for="toast in toasts" :key="toast.id">
            <div 
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="opacity-0 translate-y-[-10px] scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-[-10px] scale-95"
                class="pointer-events-auto p-4 rounded-2xl shadow-xl border flex items-center justify-between gap-3 text-sm font-bold backdrop-blur-md transition"
                :class="{
                    'bg-emerald-600/95 text-white border-emerald-500 shadow-emerald-500/20': toast.type === 'success',
                    'bg-rose-600/95 text-white border-rose-500 shadow-rose-500/20': toast.type === 'error',
                    'bg-amber-500/95 text-white border-amber-400 shadow-amber-500/20': toast.type === 'warning',
                    'bg-indigo-600/95 text-white border-indigo-500 shadow-indigo-500/20': toast.type === 'info'
                }"
            >
                <div class="flex items-center gap-2.5">
                    <span x-show="toast.type === 'success'" class="text-lg">✅</span>
                    <span x-show="toast.type === 'error'" class="text-lg">⚠️</span>
                    <span x-show="toast.type === 'warning'" class="text-lg">🔔</span>
                    <span x-show="toast.type === 'info'" class="text-lg">ℹ️</span>
                    <span x-text="toast.message" class="text-xs sm:text-sm font-semibold leading-snug"></span>
                </div>
                <button 
                    type="button" 
                    @click="removeToast(toast.id)"
                    class="opacity-70 hover:opacity-100 text-white font-bold p-1 rounded-lg transition"
                >
                    &times;
                </button>
            </div>
        </template>
    </div>

    <!-- PWA INSTALL BANNER & MODAL GUIDE -->
    <x-pwa-install-banner />

    <!-- OFFLINE AUDIO CACHE ENGINE (INDEXEDDB) -->
    <script src="/js/offline-audio-cache.js"></script>
    <!-- DNI / NIE / CIF VALIDATOR & AUTO-COMPLETER -->
    <script src="/js/dni-validator.js"></script>

    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }

        // Manejador global para apertura de enlaces despachados por Livewire (WhatsApp y URLs externas)
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-whatsapp-link', (event) => {
                const url = event?.url || (Array.isArray(event) ? event[0]?.url : null);
                if (url) window.open(url, '_blank');
            });
            Livewire.on('open-url', (event) => {
                const url = event?.url || (Array.isArray(event) ? event[0]?.url : null);
                if (url) window.open(url, '_blank');
            });
        });

        window.addEventListener('open-whatsapp-link', (e) => {
            const url = e.detail?.url || (Array.isArray(e.detail) ? e.detail[0]?.url : null);
            if (url) window.open(url, '_blank');
        });
        window.addEventListener('open-url', (e) => {
            const url = e.detail?.url || (Array.isArray(e.detail) ? e.detail[0]?.url : null);
            if (url) window.open(url, '_blank');
        });
    </script>
</body>
</html>
