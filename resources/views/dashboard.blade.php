<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Site Cloner — Автономный зеркализатор сайтов</title>

    <!-- Google Fonts: Inter & Space Grotesk (Horizon Style) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Space Grotesk', 'sans-serif'],
                        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'monospace']
                    },
                    colors: {
                        brand: {
                            50: '#fdf3ef',
                            100: '#fce5db',
                            200: '#f8c7b4',
                            300: '#f39e80',
                            400: '#ec6f47',
                            500: '#dd4814', /* Horizon Signature Terracotta */
                            600: '#ca380a',
                            700: '#a72c08',
                            800: '#86250b',
                            900: '#6d220c',
                            950: '#3a0e04',
                        },
                        obsidian: {
                            DEFAULT: '#0e0e10',
                            surface: '#18181b',
                            card: '#1e1e24',
                            border: '#27272a',
                            muted: '#54555a'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #0e0e10;
            color: #d4d4d8;
            font-family: 'Inter', sans-serif;
        }
        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Space Grotesk', sans-serif;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.1); opacity: 0.3; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }
        .pulse-activity {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .kicker {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        /* Custom scrollbar for dark surfaces */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #121214;
        }
        ::-webkit-scrollbar-thumb {
            background: #27272a;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #3f3f46;
        }
    </style>
</head>
<body class="min-h-screen antialiased selection:bg-brand-500 selection:text-white"
      x-data="siteClonerApp()"
      x-init="init()">

    <!-- Header Navigation (Horizon Style) -->
    <header class="border-b border-obsidian-border bg-obsidian/80 backdrop-blur-md sticky top-0 z-40 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('site-cloner.index') }}" class="flex items-center gap-3 group">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-brand-500 to-brand-600 flex items-center justify-center shadow-lg shadow-brand-500/20 group-hover:scale-105 transition-transform duration-300">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-xl font-bold font-heading text-white tracking-tight">SiteCloner</span>
                            <span class="text-xl font-bold font-heading text-brand-500">.</span>
                        </div>
                        <span class="text-[11px] text-zinc-400 block -mt-1 font-medium">Автономный локальный зеркализатор</span>
                    </div>
                </a>
            </div>

            <!-- Stats Bar (Horizon Meta-row style) -->
            <div class="hidden md:flex items-center gap-6">
                <div class="flex items-baseline gap-2">
                    <span class="text-xl font-bold font-heading text-white" x-text="sites.length"></span>
                    <span class="text-xs uppercase font-medium text-zinc-400">сайтов</span>
                </div>
                <div class="w-px h-6 bg-obsidian-border"></div>
                <div class="flex items-baseline gap-2">
                    <span class="text-xl font-bold font-heading text-brand-400" x-text="totalSizeFormatted"></span>
                    <span class="text-xs uppercase font-medium text-zinc-400">объем</span>
                </div>
                <div class="w-px h-6 bg-obsidian-border"></div>
                <div class="flex items-baseline gap-2">
                    <span class="text-xl font-bold font-heading text-zinc-200" x-text="totalFilesCount"></span>
                    <span class="text-xs uppercase font-medium text-zinc-400">файлов</span>
                </div>
                <div class="w-px h-6 bg-obsidian-border"></div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"></span>
                    <span class="text-xs font-semibold text-emerald-400 font-heading">Готов</span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">

        <!-- Hero & URL Input Section (Horizon Aesthetics) -->
        <section class="bg-obsidian-surface border border-obsidian-border rounded-2xl p-8 shadow-2xl relative overflow-hidden">
            <!-- Subtle Horizon brand glow -->
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-500/10 border border-brand-500/25 text-brand-400 text-xs kicker font-semibold">
                    <svg class="w-3.5 h-3.5 text-brand-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                    </svg>
                    <span>Website Archiver & Mirror Engine</span>
                </div>

                <h2 class="text-3xl sm:text-4xl font-bold font-heading text-white tracking-tight leading-tight">
                    Скачать и локализовать любой сайт
                </h2>

                <p class="text-sm sm:text-base text-zinc-400 leading-relaxed max-w-2xl">
                    Укажите URL сайта или отдельного раздела (например, демо-шаблона). Движок загрузит все страницы, скрипты, стили, шрифты и иконки для 100% автономной работы.
                </p>

                <!-- URL Input Form -->
                <form @submit.prevent="submitUrl()" class="pt-2 space-y-4">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-zinc-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                            </div>
                            <input type="url"
                                   x-model="inputUrl"
                                   placeholder="https://bootstrapmade.com/content/demo/Horizon/"
                                   required
                                   class="w-full bg-[#111114] border border-obsidian-border rounded-xl pl-11 pr-4 py-3.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-brand-500/80 focus:border-transparent transition shadow-inner">
                        </div>

                        <button type="submit"
                                :disabled="isSubmitting"
                                class="inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-brand-500 hover:bg-brand-600 active:scale-[0.98] disabled:opacity-50 text-white rounded-xl text-sm font-heading font-semibold transition duration-200 shadow-lg shadow-brand-500/25 shrink-0 cursor-pointer">
                            <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <svg x-show="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="isSubmitting ? 'Проверка...' : 'Скачать сайт'"></span>
                        </button>
                    </div>

                    <!-- Policy tags -->
                    <div class="flex flex-wrap items-center gap-y-2 gap-x-6 text-xs text-zinc-400 pt-1">
                        <span class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Строгий скоуп (только указанный путь)
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-400"></span>
                            Полные ассеты (CSS, JS, Fonts, Icons)
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            Внешние ссылки деактивируются
                        </span>
                    </div>
                </form>
            </div>
        </section>

        <!-- Active Downloads Section (With Auto-Dismiss on Finish) -->
        <section x-show="activeDownloads.length > 0"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-4"
                 x-cloak
                 class="space-y-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-brand-500"></span>
                    </span>
                    <h2 class="text-lg font-bold font-heading text-white">
                        Активные процессы (<span x-text="activeDownloads.length"></span>)
                    </h2>
                </div>
                <button @click="dismissAllCompleted()" x-show="hasCompletedTasks()" class="text-xs text-zinc-400 hover:text-white transition">
                    Очистить завершенные
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <template x-for="task in activeDownloads" :key="task.id">
                    <div class="bg-obsidian-surface border rounded-xl p-5 shadow-xl relative overflow-hidden transition-all duration-300"
                         :class="task.status === 'completed' ? 'border-emerald-500/40 bg-emerald-950/10' : (task.status === 'failed' ? 'border-rose-500/40 bg-rose-950/10' : 'border-brand-500/40 bg-obsidian-surface')">

                        <!-- Top row: ID, Badge, Timer, Dismiss -->
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                    <span class="text-xs font-mono font-medium text-brand-400 bg-brand-500/10 px-2 py-0.5 rounded border border-brand-500/20 truncate" x-text="task.id"></span>
                                    <span class="text-[11px] px-2.5 py-0.5 rounded-full font-heading font-medium tracking-wide uppercase"
                                          :class="task.status === 'completed' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : (task.status === 'failed' ? 'bg-rose-500/15 text-rose-400 border border-rose-500/30' : 'bg-brand-500/15 text-brand-400 border border-brand-500/30')">
                                        <span x-text="task.status === 'completed' ? '✓ Завершено' : (task.status === 'failed' ? 'Ошибка' : 'Скачивание...')"></span>
                                    </span>
                                </div>
                                <a :href="task.url" target="_blank" class="text-xs text-zinc-400 hover:text-white truncate block hover:underline" x-text="task.url"></a>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span class="text-xs font-mono font-bold text-zinc-300 bg-[#111114] px-2 py-1 rounded border border-obsidian-border" x-text="formatSeconds(task.elapsed_seconds)"></span>
                                <button @click="dismissTask(task.id)" class="text-zinc-500 hover:text-zinc-300 p-1 rounded transition" title="Скрыть карточку">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Live Metrics -->
                        <div class="grid grid-cols-3 gap-2 bg-[#111114] p-3 rounded-lg border border-obsidian-border text-center mb-3">
                            <div>
                                <span class="text-[10px] uppercase font-heading text-zinc-500 block">Файлов</span>
                                <span class="text-base font-bold font-heading text-white" x-text="task.files_count || 0"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-heading text-zinc-500 block">Объем</span>
                                <span class="text-base font-bold font-heading text-brand-400" x-text="task.human_size || '0 B'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase font-heading text-zinc-500 block">Страниц</span>
                                <span class="text-base font-bold font-heading text-emerald-400" x-text="task.pages_count || 0"></span>
                            </div>
                        </div>

                        <!-- Active File Ticker -->
                        <div class="text-xs mb-3">
                            <span class="text-zinc-500 text-[11px] block mb-1">Текущий файл:</span>
                            <div class="font-mono text-xs text-zinc-300 bg-[#111114] px-3 py-1.5 rounded border border-obsidian-border truncate flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-brand-500 shrink-0" :class="task.status === 'downloading' ? 'pulse-activity' : ''"></span>
                                <span class="truncate" x-text="task.current_file || 'Обработка манифеста...'"></span>
                            </div>
                        </div>

                        <!-- Completed State Actions with Auto-Dismiss Indicator -->
                        <div x-show="task.status === 'completed'" class="flex items-center gap-2 pt-2 border-t border-obsidian-border">
                            <a :href="'/' + routePrefix + '/view/' + task.id" target="_blank"
                               class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-heading font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>Открыть сайт</span>
                            </a>
                            <button @click="dismissTask(task.id)" class="px-3 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg text-xs transition">
                                Скрыть сейчас
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- Archive Gallery Section -->
        <section class="space-y-6">
            <!-- Filter & Controls Toolbar (AppDashboard Style) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-obsidian-border pb-4">
                <div>
                    <h2 class="text-2xl font-bold font-heading text-white flex items-center gap-2.5">
                        <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        <span>Локальный архив сайтов</span>
                    </h2>
                    <p class="text-xs text-zinc-400 mt-0.5">Все зеркализованные шаблоны и страницы, сохраненные на диске</p>
                </div>

                <!-- Search & Sort Controls -->
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <!-- Sort -->
                    <select x-model="sortBy" class="bg-obsidian-surface border border-obsidian-border rounded-lg px-3 py-2 text-xs text-zinc-300 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="latest">Сначала новые</option>
                        <option value="size">По размеру</option>
                        <option value="name">По названию</option>
                    </select>

                    <!-- Search Input -->
                    <div class="relative flex-1 sm:w-64">
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Поиск по сайтам..."
                               class="w-full bg-obsidian-surface border border-obsidian-border rounded-lg pl-9 pr-3 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <svg class="w-4 h-4 text-zinc-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <template x-if="filteredSites.length === 0">
                <div class="bg-obsidian-surface/60 border border-obsidian-border rounded-2xl p-16 text-center">
                    <div class="w-14 h-14 bg-zinc-800/80 rounded-2xl flex items-center justify-center mx-auto mb-4 text-zinc-500 border border-obsidian-border">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold font-heading text-white mb-1">Ничего не найдено</h3>
                    <p class="text-xs text-zinc-400">По вашему запросу не найдено ни одного сайта или архив пуст.</p>
                </div>
            </template>

            <!-- Cards Grid (Horizon & AppDashboard Cards) -->
            <div x-show="filteredSites.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <template x-for="site in filteredSites" :key="site.id">
                    <div class="bg-obsidian-surface border border-obsidian-border rounded-xl p-5 hover:border-brand-500/40 hover:shadow-xl hover:shadow-brand-500/5 transition duration-300 flex flex-col justify-between group relative overflow-hidden">

                        <!-- Top edge accent stripe on hover -->
                        <div class="absolute top-0 left-0 right-0 h-0.5 bg-brand-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>

                        <div class="space-y-4">
                            <!-- Card Header: Title & Status -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-bold font-heading text-white truncate" :title="site.id" x-text="site.id"></h3>
                                    <a :href="site.url" target="_blank" class="text-xs text-zinc-400 hover:text-brand-400 truncate block mt-0.5 hover:underline" :title="site.url" x-text="site.url"></a>
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-heading font-medium tracking-wide bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 shrink-0">
                                    ✓ Готов
                                </span>
                            </div>

                            <!-- Metrics Chips (Horizon stats pill) -->
                            <div class="grid grid-cols-3 gap-2 bg-[#111114] p-3 rounded-lg border border-obsidian-border text-center">
                                <div>
                                    <span class="text-[10px] uppercase font-heading text-zinc-400 block">Размер</span>
                                    <span class="text-xs font-bold font-heading text-brand-400" x-text="site.human_size"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-heading text-zinc-400 block">Файлов</span>
                                    <span class="text-xs font-bold font-heading text-zinc-200" x-text="site.files_count"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] uppercase font-heading text-zinc-400 block">Страниц</span>
                                    <span class="text-xs font-bold font-heading text-emerald-400" x-text="site.pages_count"></span>
                                </div>
                            </div>

                            <div class="text-[11px] text-zinc-400 flex items-center justify-between">
                                <span>Скачан:</span>
                                <span class="font-mono text-zinc-300" x-text="site.completed_at || site.started_at || 'неизвестно'"></span>
                            </div>
                        </div>

                        <!-- Card Actions Footer -->
                        <div class="flex items-center gap-2 pt-4 mt-4 border-t border-obsidian-border">
                            <!-- Primary Open Button -->
                            <a :href="'/' + routePrefix + '/view/' + site.id" target="_blank"
                               class="flex-1 inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-lg text-xs font-heading font-semibold transition shadow-sm shadow-brand-500/20">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>Открыть</span>
                            </a>

                            <!-- View Pages List Button (AppDashboard tool) -->
                            <button @click="openPagesModal(site)"
                                    class="px-2.5 py-2 bg-[#111114] hover:bg-zinc-800 text-zinc-300 rounded-lg text-xs font-heading border border-obsidian-border transition"
                                    title="Посмотреть все страницы">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </button>

                            <!-- Delete Button -->
                            <button @click="confirmDelete(site)"
                                    class="p-2 text-zinc-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition"
                                    title="Удалить сайт">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </main>

    <!-- Pages List Modal (Inspect All Pages inside site) -->
    <div x-show="pagesModalSite !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-obsidian/80 backdrop-blur-md">
        <div @click.away="pagesModalSite = null" class="bg-obsidian-surface border border-obsidian-border rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-obsidian-border">
                <div class="min-w-0 flex-1">
                    <span class="text-xs uppercase font-heading text-brand-400 tracking-wider font-semibold">Список страниц</span>
                    <h3 class="font-bold font-heading text-white text-base truncate" x-text="pagesModalSite ? pagesModalSite.id : ''"></h3>
                </div>
                <button @click="pagesModalSite = null" class="text-zinc-400 hover:text-white p-1 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Page search -->
            <div class="relative">
                <input type="text"
                       x-model="pagesSearchQuery"
                       placeholder="Быстрый фильтр страниц..."
                       class="w-full bg-[#111114] border border-obsidian-border rounded-lg pl-9 pr-3 py-2 text-xs text-white placeholder-zinc-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                <svg class="w-3.5 h-3.5 text-zinc-500 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Pages Scroll Area -->
            <div class="overflow-y-auto space-y-1.5 flex-1 pr-1">
                <template x-for="page in filteredModalPages" :key="page">
                    <a :href="'/' + routePrefix + '/view/' + pagesModalSite.id + '/' + page" target="_blank"
                       class="flex items-center justify-between p-2.5 rounded-lg bg-[#111114] hover:bg-zinc-800/80 border border-obsidian-border text-xs text-zinc-300 hover:text-white transition group">
                        <span class="font-mono truncate" x-text="page"></span>
                        <span class="text-[11px] font-heading text-brand-400 group-hover:translate-x-0.5 transition-transform flex items-center gap-1">
                            Открыть
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </a>
                </template>
                <template x-if="filteredModalPages.length === 0">
                    <p class="text-xs text-zinc-500 text-center py-6">Страниц не найдено.</p>
                </template>
            </div>

            <div class="pt-3 border-t border-obsidian-border flex justify-end">
                <button @click="pagesModalSite = null" class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-white rounded-lg text-xs font-heading font-semibold transition">
                    Закрыть
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="siteToDelete !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-obsidian/80 backdrop-blur-md">
        <div @click.away="siteToDelete = null" class="bg-obsidian-surface border border-obsidian-border rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center gap-3 text-rose-400">
                <div class="w-11 h-11 rounded-xl bg-rose-500/10 flex items-center justify-center border border-rose-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold font-heading text-white text-base">Подтверждение удаления</h3>
                    <span class="text-xs text-zinc-400">Действие необратимо</span>
                </div>
            </div>

            <p class="text-sm text-zinc-300">
                Вы действительно хотите удалить сайт <code class="text-rose-400 font-mono text-xs bg-[#111114] px-1.5 py-0.5 rounded" x-text="siteToDelete ? siteToDelete.id : ''"></code>?
            </p>
            <p class="text-xs text-zinc-500">
                Все скачанные HTML-файлы, CSS, JS, шрифты и картинки будут безвозвратно стёрты с диска.
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button @click="siteToDelete = null" class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg text-xs font-heading font-semibold transition">
                    Отмена
                </button>
                <button @click="executeDelete()" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-heading font-semibold transition shadow-lg shadow-rose-600/20">
                    Да, удалить
                </button>
            </div>
        </div>
    </div>

    <!-- Duplicate Warning Modal -->
    <div x-show="duplicateModal !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-obsidian/80 backdrop-blur-md">
        <div @click.away="duplicateModal = null" class="bg-obsidian-surface border border-obsidian-border rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center gap-3 text-amber-400">
                <div class="w-11 h-11 rounded-xl bg-amber-500/10 flex items-center justify-center border border-amber-500/20 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold font-heading text-white text-base">Сайт уже скачан</h3>
                    <span class="text-xs text-zinc-400">Обнаружена локальная копия</span>
                </div>
            </div>

            <p class="text-sm text-zinc-300">
                Этот URL уже есть в вашем локальном архиве:
                <span class="block font-mono text-xs text-amber-400 bg-[#111114] p-2.5 rounded mt-1.5 break-all border border-obsidian-border" x-text="duplicateModal ? duplicateModal.id : ''"></span>
            </p>
            <p class="text-xs text-zinc-500">
                Вы можете открыть уже сохраненную копию прямо сейчас или перезакачать её с нуля.
            </p>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button @click="duplicateModal = null" class="px-3.5 py-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg text-xs font-heading font-medium transition">
                    Отмена
                </button>
                <a :href="'/' + routePrefix + '/view/' + (duplicateModal ? duplicateModal.id : '')" target="_blank"
                   @click="duplicateModal = null"
                   class="px-3.5 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-lg text-xs font-heading font-semibold transition">
                    Открыть сохраненный
                </a>
                <button @click="startDownload(duplicateModal.url, true); duplicateModal = null;"
                        class="px-3.5 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-heading font-semibold transition">
                    Перезакачать
                </button>
            </div>
        </div>
    </div>

    <!-- Alpine.js Application Logic -->
    <script>
        function siteClonerApp() {
            return {
                routePrefix: '{{ $routePrefix }}',
                inputUrl: '',
                isSubmitting: false,
                searchQuery: '',
                sortBy: 'latest',
                siteToDelete: null,
                duplicateModal: null,
                pagesModalSite: null,
                pagesSearchQuery: '',
                activeDownloads: [],
                sites: @json(array_map(fn($s) => $s->toArray(), $sites)),
                totalSizeFormatted: '{{ $totalSizeFormatted }}',

                init() {
                    // Check for unfinished sites on load
                    this.sites.forEach(site => {
                        if (site.status === 'downloading' || site.status === 'pending') {
                            this.connectSSE(site.id, site.url);
                        }
                    });
                },

                get totalFilesCount() {
                    return this.sites.reduce((acc, s) => acc + (s.files_count || 0), 0);
                },

                get filteredSites() {
                    let list = [...this.sites];

                    if (this.searchQuery.trim()) {
                        const q = this.searchQuery.toLowerCase();
                        list = list.filter(s =>
                            s.id.toLowerCase().includes(q) ||
                            (s.url && s.url.toLowerCase().includes(q))
                        );
                    }

                    if (this.sortBy === 'size') {
                        list.sort((a, b) => (b.total_bytes || 0) - (a.total_bytes || 0));
                    } else if (this.sortBy === 'name') {
                        list.sort((a, b) => a.id.localeCompare(b.id));
                    } else {
                        // latest
                        list.sort((a, b) => (b.started_at || '').localeCompare(a.started_at || ''));
                    }

                    return list;
                },

                get filteredModalPages() {
                    if (!this.pagesModalSite || !this.pagesModalSite.pages) return [];
                    if (!this.pagesSearchQuery.trim()) return this.pagesModalSite.pages;
                    const q = this.pagesSearchQuery.toLowerCase();
                    return this.pagesModalSite.pages.filter(p => p.toLowerCase().includes(q));
                },

                openPagesModal(site) {
                    this.pagesModalSite = site;
                    this.pagesSearchQuery = '';
                },

                formatSeconds(secs) {
                    if (!secs) return '00:00';
                    const m = Math.floor(secs / 60).toString().padStart(2, '0');
                    const s = (secs % 60).toString().padStart(2, '0');
                    return `${m}:${s}`;
                },

                hasCompletedTasks() {
                    return this.activeDownloads.some(t => t.status === 'completed');
                },

                dismissAllCompleted() {
                    this.activeDownloads = this.activeDownloads.filter(t => t.status !== 'completed');
                },

                dismissTask(taskId) {
                    this.activeDownloads = this.activeDownloads.filter(t => t.id !== taskId);
                },

                async submitUrl() {
                    if (!this.inputUrl.trim()) return;
                    this.isSubmitting = true;

                    try {
                        const checkRes = await fetch(`/${this.routePrefix}/check`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ url: this.inputUrl })
                        });

                        const checkData = await checkRes.json();
                        if (checkData.exists) {
                            this.duplicateModal = {
                                id: checkData.id,
                                url: this.inputUrl,
                                manifest: checkData.manifest
                            };
                            this.isSubmitting = false;
                            return;
                        }

                        await this.startDownload(this.inputUrl, false);
                        this.inputUrl = '';
                    } catch (err) {
                        alert('Ошибка проверки URL: ' + err.message);
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                async startDownload(url, force = false) {
                    try {
                        const res = await fetch(`/${this.routePrefix}/start`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ url: url, force: force })
                        });

                        const data = await res.json();
                        if (res.status === 409) {
                            this.duplicateModal = { id: data.id, url: url, manifest: data.manifest };
                            return;
                        }

                        if (!data.success) {
                            alert(data.error || 'Не удалось запустить скачивание');
                            return;
                        }

                        this.connectSSE(data.id, url);
                    } catch (err) {
                        alert('Ошибка запроса: ' + err.message);
                    }
                },

                connectSSE(siteId, originalUrl) {
                    let task = this.activeDownloads.find(t => t.id === siteId);
                    if (!task) {
                        task = {
                            id: siteId,
                            url: originalUrl,
                            status: 'downloading',
                            files_count: 0,
                            total_bytes: 0,
                            pages_count: 0,
                            human_size: '0 B',
                            current_file: 'Подключение к потоку...',
                            elapsed_seconds: 0
                        };
                        this.activeDownloads.unshift(task);
                    }

                    const evtSource = new EventSource(`/${this.routePrefix}/progress/${siteId}`);

                    evtSource.onmessage = (e) => {
                        try {
                            const updated = JSON.parse(e.data);
                            Object.assign(task, updated);

                            if (task.status === 'completed' || task.status === 'failed') {
                                evtSource.close();
                                this.refreshSitesList(task);

                                // Auto-dismiss completed task after 3.5s so window doesn't stick!
                                if (task.status === 'completed') {
                                    setTimeout(() => {
                                        this.dismissTask(task.id);
                                    }, 3500);
                                }
                            }
                        } catch (err) {
                            console.error('SSE JSON error', err);
                        }
                    };

                    evtSource.addEventListener('done', () => {
                        evtSource.close();
                    });

                    evtSource.onerror = (err) => {
                        console.warn('SSE connection error', err);
                    };
                },

                refreshSitesList(completedSite) {
                    const idx = this.sites.findIndex(s => s.id === completedSite.id);
                    if (idx >= 0) {
                        this.sites[idx] = completedSite;
                    } else {
                        this.sites.unshift(completedSite);
                    }
                    this.recalculateTotalSize();
                },

                recalculateTotalSize() {
                    const totalBytes = this.sites.reduce((acc, s) => acc + (s.total_bytes || 0), 0);
                    if (totalBytes <= 0) {
                        this.totalSizeFormatted = '0 B';
                        return;
                    }
                    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.min(Math.floor(Math.log(totalBytes) / Math.log(1024)), units.length - 1);
                    this.totalSizeFormatted = (totalBytes / Math.pow(1024, i)).toFixed(2) + ' ' + units[i];
                },

                confirmDelete(site) {
                    this.siteToDelete = site;
                },

                async executeDelete() {
                    if (!this.siteToDelete) return;
                    const id = this.siteToDelete.id;

                    try {
                        const res = await fetch(`/${this.routePrefix}/sites/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });

                        const data = await res.json();
                        if (data.success) {
                            this.sites = this.sites.filter(s => s.id !== id);
                            this.dismissTask(id);
                            this.recalculateTotalSize();
                        } else {
                            alert('Не удалось удалить сайт');
                        }
                    } catch (err) {
                        alert('Ошибка удаления: ' + err.message);
                    } finally {
                        this.siteToDelete = null;
                    }
                }
            };
        }
    </script>
</body>
</html>
