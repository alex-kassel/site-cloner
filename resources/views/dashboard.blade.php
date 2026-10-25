<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Site Cloner — Локальный архив сайтов</title>

    <!-- Google Fonts: Space Grotesk & Inter (Horizon Style) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        heading: ['Space Grotesk', 'sans-serif'],
                        mono: ['ui-monospace', 'Menlo', 'Monaco', 'Consolas', 'monospace']
                    },
                    colors: {
                        brand: {
                            50: '#fdf3ef',
                            100: '#fce5db',
                            200: '#f8c7b4',
                            300: '#f39e80',
                            DEFAULT: '#dd4814',
                            hover: '#c83c0c',
                            active: '#b23308',
                        },
                        sand: {
                            50: '#faf9f6',
                            100: '#f4f3f0', /* Horizon Signature light-background */
                            200: '#e8e6df',
                            300: '#d7d4ca',
                        },
                        ink: {
                            900: '#0e0e10', /* Horizon heading color */
                            600: '#54555a', /* Horizon default text color */
                            400: '#7e7f86',
                            200: '#e4e4e7',
                        }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            background-color: #f4f3f0;
            color: #54555a;
            font-family: 'Inter', system-ui, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4, .font-heading {
            font-family: 'Space Grotesk', sans-serif;
            color: #0e0e10;
        }
        ::selection {
            background: #dd4814;
            color: #ffffff;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-sand-100 text-ink-600" x-data="siteClonerApp()" x-init="init()">

    <!-- Top Navigation (Generous, crisp Horizon header) -->
    <header class="border-b border-sand-200 bg-white/95 sticky top-0 z-40 backdrop-blur shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <a href="{{ route('site-cloner.index') }}" class="flex items-center gap-2.5 group">
                    <span class="w-3.5 h-3.5 rounded-full bg-brand group-hover:scale-125 transition-transform"></span>
                    <span class="text-2xl font-bold font-heading text-ink-900 tracking-tight">Site Cloner</span>
                </a>
                <span class="text-sm text-sand-300 select-none">/</span>
                <span class="text-sm text-ink-400 font-medium">Локальный архив сайтов</span>
            </div>

            <!-- Stats Bar -->
            <div class="flex items-center gap-6 text-sm">
                <div class="flex items-baseline gap-2">
                    <span class="text-ink-400">Сайтов:</span>
                    <span class="font-bold text-ink-900 font-heading text-lg" x-text="sites.length"></span>
                </div>
                <div class="w-px h-4 bg-sand-200"></div>
                <div class="flex items-baseline gap-2">
                    <span class="text-ink-400">Объем:</span>
                    <span class="font-bold text-brand font-heading text-lg" x-text="totalSizeFormatted"></span>
                </div>
                <div class="w-px h-4 bg-sand-200"></div>
                <div class="flex items-baseline gap-2">
                    <span class="text-ink-400">Файлов:</span>
                    <span class="font-bold text-ink-900 font-heading text-lg" x-text="totalFilesCount"></span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-10 flex-1 w-full space-y-10">

        <!-- Download Input Workbench -->
        <section class="bg-white border border-sand-200 rounded-2xl p-7 sm:p-9 shadow-sm relative overflow-hidden">
            <div class="max-w-3xl space-y-4">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-50 border border-brand-200 text-brand text-xs sm:text-sm font-heading font-semibold">
                    <span class="w-2 h-2 rounded-full bg-brand"></span>
                    <span>Автономное клонирование веб-шаблонов</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-bold font-heading text-ink-900 tracking-tight leading-tight">
                    Скачать сайт или демо-шаблон
                </h1>
                <p class="text-base sm:text-lg text-ink-600 leading-relaxed">
                    Введите URL страницы или каталога. Движок полностью скачает все HTML-страницы внутри этого пути, подтянет стили, JavaScript, иконки и шрифты для автономной работы офлайн.
                </p>

                <form @submit.prevent="submitUrl()" class="pt-2 space-y-4">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <input type="url"
                                   x-model="inputUrl"
                                   placeholder="https://laravel.com/framework/docs"
                                   required
                                   class="w-full bg-white border border-sand-300 rounded-xl px-4 py-3.5 text-base text-ink-900 placeholder-ink-400 focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand-100 transition shadow-inner">
                        </div>

                        <button type="submit"
                                :disabled="isSubmitting"
                                class="inline-flex items-center justify-center gap-2 px-7 py-3.5 bg-brand hover:bg-brand-hover active:bg-brand-active disabled:opacity-50 text-white rounded-xl text-base font-heading font-semibold transition shadow-md shadow-brand/20 shrink-0 cursor-pointer">
                            <svg x-show="isSubmitting" class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <svg x-show="!isSubmitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span x-text="isSubmitting ? 'Проверка...' : 'Скачать сайт'"></span>
                        </button>
                    </div>

                    <!-- Mode Selector -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-2 border-t border-sand-200">
                        <div class="flex items-center gap-2.5 shrink-0">
                            <span class="text-xs font-semibold text-ink-500 uppercase tracking-wider">Режим:</span>
                            <div class="inline-flex items-center p-1 bg-sand-100 rounded-xl shrink-0">
                                <button type="button"
                                        @click="downloadMode = 'light'"
                                        :class="downloadMode === 'light' ? 'bg-white text-ink-900 shadow-sm font-semibold' : 'text-ink-500 hover:text-ink-800'"
                                        class="px-3.5 py-1.5 rounded-lg text-xs font-heading transition inline-flex items-center gap-1.5 whitespace-nowrap shrink-0">
                                    <span class="shrink-0 text-sm">⚡</span>
                                    <span class="whitespace-nowrap">Лёгкий</span>
                                </button>
                                <button type="button"
                                        @click="downloadMode = 'full'"
                                        :class="downloadMode === 'full' ? 'bg-white text-ink-900 shadow-sm font-semibold' : 'text-ink-500 hover:text-ink-800'"
                                        class="px-3.5 py-1.5 rounded-lg text-xs font-heading transition inline-flex items-center gap-1.5 whitespace-nowrap shrink-0">
                                    <span class="shrink-0 text-sm">✈️</span>
                                    <span class="whitespace-nowrap">Полный</span>
                                </button>
                            </div>
                        </div>
                        <div class="text-xs text-ink-500">
                            <span x-show="downloadMode === 'light'">⚡ <strong>Легкий (быстро):</strong> чистый код шаблона, внешние CDN остаются оригинальными ссылками.</span>
                            <span x-show="downloadMode === 'full'">✈️ <strong>Полный (100% офлайн):</strong> все внешние библиотеки и картинки скачиваются локально.</span>
                        </div>
                    </div>

                    <!-- Clean helper notes -->
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-ink-600 pt-1">
                        <span class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Строго внутри указанного пути
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-brand"></span>
                            Шрифты и ассеты скачиваются полностью
                        </span>
                        <span class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Внешние ссылки за пределы пути деактивируются
                        </span>
                    </div>
                </form>
            </div>
        </section>

        <!-- Archive Section (Clean White Cards on Light Sand) -->
        <section class="space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-sand-200 pb-4">
                <div>
                    <h2 class="text-2xl font-bold font-heading text-ink-900">
                        Архив сайтов
                    </h2>
                    <p class="text-sm text-ink-400">Сохраненные локальные копии сайтов</p>
                </div>

                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <!-- Search -->
                    <div class="relative flex-1 sm:w-56">
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Поиск по сайтам..."
                               class="w-full bg-white border border-sand-300 rounded-xl px-3.5 py-2 text-sm text-ink-900 placeholder-ink-400 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand-100">
                    </div>

                    <!-- Sort -->
                    <select x-model="sortBy" class="bg-white border border-sand-300 rounded-xl px-3 py-2 text-sm text-ink-600 focus:outline-none focus:border-brand">
                        <option value="latest">По дате</option>
                        <option value="size">По размеру</option>
                        <option value="name">По имени</option>
                    </select>

                    <!-- Grid / List Switcher -->
                    <div class="inline-flex p-1 bg-sand-100 rounded-xl shrink-0">
                        <button type="button"
                                @click="viewMode = 'grid'"
                                :class="viewMode === 'grid' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-400 hover:text-ink-700'"
                                class="p-2 rounded-lg transition"
                                title="Сетка">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                        </button>
                        <button type="button"
                                @click="viewMode = 'list'"
                                :class="viewMode === 'list' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-400 hover:text-ink-700'"
                                class="p-2 rounded-lg transition"
                                title="Список">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty Archive -->
            <template x-if="filteredSites.length === 0">
                <div class="bg-white border border-sand-200 rounded-2xl p-14 text-center space-y-2 shadow-sm">
                    <p class="text-base font-semibold font-heading text-ink-900">В архиве пока нет сайтов</p>
                    <p class="text-sm text-ink-400">Вставьте адрес сайта в форму выше, чтобы сохранить локальную копию.</p>
                </div>
            </template>

            <!-- Sites Grid (Generous, readable Horizon-styled cards) -->
            <div x-show="filteredSites.length > 0 && viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="site in filteredSites" :key="site.id">
                    <div class="bg-white border rounded-2xl p-6 flex flex-col justify-between transition duration-200 space-y-4 shadow-sm group"
                         :class="site.status === 'downloading' ? 'border-brand ring-2 ring-brand/10' : (site.status === 'failed' ? 'border-rose-300' : 'border-sand-200 hover:border-brand-200 hover:shadow-md')">

                        <div class="space-y-3.5">
                            <div class="flex items-start justify-between gap-2.5">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base sm:text-lg font-bold font-heading text-ink-900 truncate" :title="site.id" x-text="site.id"></h3>
                                    <a :href="site.url" target="_blank" class="text-sm text-ink-400 hover:text-brand truncate block mt-0.5" :title="site.url" x-text="site.url"></a>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold border"
                                          :class="site.download_mode === 'full' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                          x-text="site.download_mode === 'full' ? '✈️ Полный' : '⚡ Лёгкий'">
                                    </span>

                                    <!-- Status Badge -->
                                    <template x-if="site.status === 'downloading'">
                                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-brand animate-ping"></span>
                                            <span>Загрузка...</span>
                                        </span>
                                    </template>
                                    <template x-if="site.status === 'completed'">
                                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Готов
                                        </span>
                                    </template>
                                    <template x-if="site.status === 'failed'">
                                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            Ошибка
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Metrics row with guaranteed single-line sizes -->
                            <div class="grid grid-cols-3 gap-2 bg-sand-50 p-3 rounded-xl border border-sand-200 text-center">
                                <div class="min-w-0">
                                    <span class="text-xs text-ink-400 block font-medium">Размер</span>
                                    <span class="text-xs sm:text-sm font-bold font-mono text-brand whitespace-nowrap truncate block" x-text="site.human_size || '0 B'"></span>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-xs text-ink-400 block font-medium">Файлов</span>
                                    <span class="text-xs sm:text-sm font-bold font-mono text-ink-900 whitespace-nowrap truncate block" x-text="site.files_count || 0"></span>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-xs text-ink-400 block font-medium">Страниц</span>
                                    <span class="text-xs sm:text-sm font-bold font-mono text-emerald-700 whitespace-nowrap truncate block" x-text="site.pages_count || 0"></span>
                                </div>
                            </div>

                            <!-- Live file ticker while downloading, or completed date -->
                            <template x-if="site.status === 'downloading'">
                                <div class="flex items-center justify-between text-xs bg-sand-100/70 px-3 py-2 rounded-xl border border-sand-200">
                                    <span class="text-ink-600 font-mono truncate mr-2" x-text="site.current_file || 'Инициализация...' "></span>
                                    <span class="font-mono text-ink-900 font-bold shrink-0" x-text="formatSeconds(site.elapsed_seconds)"></span>
                                </div>
                            </template>
                            <template x-if="site.status !== 'downloading'">
                                <div class="text-xs sm:text-sm text-ink-400 flex items-center justify-between">
                                    <span>Скачан:</span>
                                    <span class="font-mono text-ink-600" x-text="site.completed_at || site.started_at || 'неизвестно'"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Card Action Buttons: Downloading State vs Completed State -->
                        <template x-if="site.status === 'downloading'">
                            <div class="pt-3.5 border-t border-sand-200">
                                <button type="button" disabled class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-sand-100 text-ink-600 rounded-xl text-sm font-heading font-medium cursor-wait">
                                    <svg class="animate-spin w-4 h-4 text-brand" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Скачивание (<span x-text="formatSeconds(site.elapsed_seconds)"></span>)...</span>
                                </button>
                            </div>
                        </template>

                        <template x-if="site.status === 'completed'">
                            <div class="flex items-center gap-2.5 pt-3.5 border-t border-sand-200">
                                <!-- Open in viewer -->
                                <a :href="'/' + routePrefix + '/view/' + site.id" target="_blank"
                                   class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-brand hover:bg-brand-hover text-white rounded-xl text-sm font-heading font-semibold transition shadow-sm shadow-brand/20 whitespace-nowrap">
                                    <span>Открыть сайт</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>

                                <!-- Options Dropdown -->
                                <div x-data="{ openOptions: false }" class="relative">
                                    <button type="button"
                                            @click="openOptions = !openOptions"
                                            @click.away="openOptions = false"
                                            class="px-3.5 py-2.5 bg-sand-100 hover:bg-sand-200 text-ink-700 rounded-xl text-sm font-heading font-semibold transition flex items-center gap-1.5 shrink-0"
                                            title="Опции сайта">
                                        <span>Опции</span>
                                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="openOptions ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <!-- Dropdown Menu -->
                                    <div x-show="openOptions"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         x-cloak
                                         class="absolute right-0 bottom-full mb-2 w-52 bg-white border border-sand-200 rounded-2xl shadow-xl z-30 p-1.5 space-y-0.5">

                                        <button type="button"
                                                @click="openPagesModal(site); openOptions = false"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-ink-700 hover:text-ink-900 hover:bg-sand-50 rounded-xl transition text-left">
                                            <svg class="w-4 h-4 text-ink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span>Страницы сайта</span>
                                        </button>

                                        <button type="button"
                                                @click="openResourcesModal(site); openOptions = false"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-ink-700 hover:text-ink-900 hover:bg-sand-50 rounded-xl transition text-left">
                                            <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                            </svg>
                                            <span>Ресурсы и CDN</span>
                                        </button>

                                        <a :href="'/' + routePrefix + '/export/' + site.id"
                                           @click="openOptions = false"
                                           class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-ink-700 hover:text-ink-900 hover:bg-sand-50 rounded-xl transition text-left">
                                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                            </svg>
                                            <span>Скачать ZIP</span>
                                        </a>

                                        <div class="border-t border-sand-100 my-1"></div>

                                        <button type="button"
                                                @click="confirmDelete(site); openOptions = false"
                                                class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 rounded-xl transition text-left font-medium">
                                            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            <span>Удалить сайт</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <template x-if="site.status === 'failed'">
                            <div class="pt-3.5 border-t border-sand-200 flex items-center justify-between">
                                <span class="text-xs text-rose-600 font-medium truncate" x-text="site.error_message || 'Ошибка загрузки'"></span>
                                <button type="button" @click="confirmDelete(site)" class="text-xs text-rose-600 hover:underline">Удалить</button>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- Sites List (Compact Table / Rows) -->
            <div x-show="filteredSites.length > 0 && viewMode === 'list'" class="space-y-3">
                <template x-for="site in filteredSites" :key="site.id">
                    <div class="bg-white border rounded-2xl p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 transition"
                         :class="site.status === 'downloading' ? 'border-brand ring-2 ring-brand/10' : (site.status === 'failed' ? 'border-rose-300' : 'border-sand-200 hover:border-brand-200 hover:shadow-sm')">
                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-bold font-heading text-ink-900 truncate" :title="site.id" x-text="site.id"></h3>
                                <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold border shrink-0"
                                      :class="site.download_mode === 'full' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-amber-50 text-amber-700 border-amber-200'"
                                      x-text="site.download_mode === 'full' ? '✈️ Полный' : '⚡ Лёгкий'">
                                </span>

                                <template x-if="site.status === 'downloading'">
                                    <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-brand animate-ping"></span>
                                        <span>Загрузка...</span>
                                    </span>
                                </template>
                                <template x-if="site.status === 'completed'">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                        Готов
                                    </span>
                                </template>
                                <template x-if="site.status === 'failed'">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold bg-rose-50 text-rose-700 border border-rose-200 shrink-0">
                                        Ошибка
                                    </span>
                                </template>
                            </div>
                            <a :href="site.url" target="_blank" class="text-xs sm:text-sm text-ink-400 hover:text-brand truncate block" :title="site.url" x-text="site.url"></a>
                        </div>

                        <!-- List metrics -->
                        <div class="flex items-center gap-4 sm:gap-6 text-sm shrink-0">
                            <div>
                                <span class="text-xs text-ink-400 block font-medium">Размер</span>
                                <span class="font-mono font-bold text-brand whitespace-nowrap" x-text="site.human_size || '0 B'"></span>
                            </div>
                            <div>
                                <span class="text-xs text-ink-400 block font-medium">Файлов</span>
                                <span class="font-mono font-bold text-ink-900 whitespace-nowrap" x-text="site.files_count || 0"></span>
                            </div>
                            <div>
                                <span class="text-xs text-ink-400 block font-medium">Страниц</span>
                                <span class="font-mono font-bold text-emerald-700 whitespace-nowrap" x-text="site.pages_count || 0"></span>
                            </div>
                            <div class="hidden sm:block">
                                <span class="text-xs text-ink-400 block font-medium" x-text="site.status === 'downloading' ? 'Таймер' : 'Скачан'"></span>
                                <span class="font-mono text-xs text-ink-600 whitespace-nowrap" x-text="site.status === 'downloading' ? formatSeconds(site.elapsed_seconds) : (site.completed_at || site.started_at || '').slice(0, 10)"></span>
                            </div>
                        </div>

                        <!-- List actions -->
                        <div class="flex items-center gap-2.5 shrink-0 pt-2 md:pt-0 border-t md:border-t-0 border-sand-100">
                            <template x-if="site.status === 'downloading'">
                                <button type="button" disabled class="inline-flex items-center gap-1.5 px-4 py-2 bg-sand-100 text-ink-600 rounded-xl text-sm font-heading font-medium cursor-wait">
                                    <svg class="animate-spin w-3.5 h-3.5 text-brand" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    <span>Загрузка...</span>
                                </button>
                            </template>

                            <template x-if="site.status === 'completed'">
                                <div class="flex items-center gap-2">
                                    <a :href="'/' + routePrefix + '/view/' + site.id" target="_blank"
                                       class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand hover:bg-brand-hover text-white rounded-xl text-sm font-heading font-semibold transition shadow-sm shadow-brand/20 whitespace-nowrap">
                                        <span>Открыть</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>

                                    <div x-data="{ openOptions: false }" class="relative">
                                        <button type="button"
                                                @click="openOptions = !openOptions"
                                                @click.away="openOptions = false"
                                                class="px-3 py-2 bg-sand-100 hover:bg-sand-200 text-ink-700 rounded-xl text-sm font-heading font-semibold transition flex items-center gap-1.5 shrink-0">
                                            <span>Опции</span>
                                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="openOptions ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>

                                        <div x-show="openOptions"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="opacity-100 scale-100"
                                             x-transition:leave-end="opacity-0 scale-95"
                                             x-cloak
                                             class="absolute right-0 top-full mt-2 w-52 bg-white border border-sand-200 rounded-2xl shadow-xl z-30 p-1.5 space-y-0.5">

                                            <button type="button"
                                                    @click="openPagesModal(site); openOptions = false"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-ink-700 hover:text-ink-900 hover:bg-sand-50 rounded-xl transition text-left">
                                                <svg class="w-4 h-4 text-ink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <span>Страницы сайта</span>
                                            </button>

                                            <button type="button"
                                                    @click="openResourcesModal(site); openOptions = false"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-ink-700 hover:text-ink-900 hover:bg-sand-50 rounded-xl transition text-left">
                                                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                                <span>Ресурсы и CDN</span>
                                            </button>

                                            <a :href="'/' + routePrefix + '/export/' + site.id"
                                               @click="openOptions = false"
                                               class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-ink-700 hover:text-ink-900 hover:bg-sand-50 rounded-xl transition text-left">
                                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                <span>Скачать ZIP</span>
                                            </a>

                                            <div class="border-t border-sand-100 my-1"></div>

                                            <button type="button"
                                                    @click="confirmDelete(site); openOptions = false"
                                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-rose-600 hover:bg-rose-50 rounded-xl transition text-left font-medium">
                                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                <span>Удалить сайт</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </main>

    <!-- Pages List Modal -->
    <div x-show="pagesModalSite !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink-900/40 backdrop-blur-sm">
        <div @click.away="pagesModalSite = null" class="bg-white border border-sand-200 rounded-2xl max-w-xl w-full p-7 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between pb-3.5 border-b border-sand-200">
                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-bold font-heading text-ink-900 truncate" x-text="'Страницы: ' + (pagesModalSite ? pagesModalSite.id : '')"></h3>
                    <p class="text-sm text-ink-400">Выберите страницу для открытия в браузере</p>
                </div>
                <button @click="pagesModalSite = null" class="text-ink-400 hover:text-ink-900 text-lg leading-none p-1.5">
                    ✕
                </button>
            </div>

            <div class="relative">
                <input type="text"
                       x-model="pagesSearchQuery"
                       placeholder="Фильтр страниц..."
                       class="w-full bg-sand-50 border border-sand-200 rounded-xl px-4 py-2.5 text-sm text-ink-900 placeholder-ink-400 focus:outline-none focus:border-brand">
            </div>

            <div class="overflow-y-auto space-y-2 flex-1 pr-1">
                <template x-for="page in filteredModalPages" :key="page">
                    <a :href="'/' + routePrefix + '/view/' + pagesModalSite.id + '/' + page" target="_blank"
                       class="flex items-center justify-between p-3 rounded-xl bg-sand-50 hover:bg-sand-100 border border-sand-200 text-sm text-ink-600 hover:text-ink-900 transition">
                        <span class="font-mono truncate" x-text="page"></span>
                        <span class="text-brand font-heading text-sm font-semibold shrink-0 ml-2">Открыть →</span>
                    </a>
                </template>
                <template x-if="filteredModalPages.length === 0">
                    <p class="text-sm text-ink-400 text-center py-8">Страниц не найдено.</p>
                </template>
            </div>

            <div class="pt-3.5 border-t border-sand-200 flex justify-end">
                <button @click="pagesModalSite = null" class="px-5 py-2.5 bg-sand-100 hover:bg-sand-200 text-ink-900 rounded-xl text-sm font-heading font-semibold transition">
                    Закрыть
                </button>
            </div>
        </div>
    </div>

    <!-- External Resources Inspection Modal -->
    <div x-show="resourcesModalSite !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink-900/40 backdrop-blur-sm">
        <div @click.away="resourcesModalSite = null" class="bg-white border border-sand-200 rounded-3xl max-w-4xl w-full p-7 shadow-2xl space-y-5 max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between pb-4 border-b border-sand-200">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 text-xs font-semibold uppercase tracking-wider rounded-lg bg-sand-100 text-ink-600">Аудит зависимостей</span>
                        <h3 class="text-xl font-bold font-heading text-ink-900 truncate" x-text="resourcesModalSite ? resourcesModalSite.id : ''"></h3>
                    </div>
                    <p class="text-sm text-ink-500 mt-1">Внешние библиотеки, CDN, шрифты, медиафайлы и заблокированные ссылки</p>
                </div>
                <button @click="resourcesModalSite = null" class="text-ink-400 hover:text-ink-900 text-xl leading-none p-2 rounded-xl hover:bg-sand-100 transition">
                    ✕
                </button>
            </div>

            <!-- Loading State -->
            <div x-show="isLoadingResources" class="py-16 text-center space-y-3">
                <svg class="animate-spin h-8 w-8 text-brand mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <p class="text-sm font-medium text-ink-600">Анализ внешних ресурсов и библиотек...</p>
            </div>

            <!-- Loaded Content -->
            <div x-show="!isLoadingResources && resourcesData" class="flex-1 overflow-y-auto space-y-5 pr-1">
                <!-- Summary Stats Counters -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-sand-50 border border-sand-200 rounded-2xl p-4">
                        <span class="text-xs font-medium text-ink-400 uppercase tracking-wider block">Всего доменов</span>
                        <span class="text-2xl font-bold font-heading text-ink-900 mt-1 block" x-text="resourcesData?.summary?.total_domains || 0"></span>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4">
                        <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wider block">💾 Офлайн (скачано)</span>
                        <span class="text-2xl font-bold font-heading text-emerald-700 mt-1 block" x-text="resourcesData?.summary?.downloaded_count || 0"></span>
                    </div>
                    <div class="bg-sky-50 border border-sky-200 rounded-2xl p-4">
                        <span class="text-xs font-semibold text-sky-800 uppercase tracking-wider block">🔗 Внешние CDN</span>
                        <span class="text-2xl font-bold font-heading text-sky-700 mt-1 block" x-text="resourcesData?.summary?.external_count || 0"></span>
                    </div>
                    <div class="bg-sand-100 border border-sand-200 rounded-2xl p-4">
                        <span class="text-xs font-semibold text-ink-600 uppercase tracking-wider block">🚫 Заблокировано</span>
                        <span class="text-2xl font-bold font-heading text-ink-700 mt-1 block" x-text="resourcesData?.summary?.deactivated_count || 0"></span>
                    </div>
                </div>

                <!-- Filter Tabs & Search -->
                <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center justify-between">
                    <div class="flex items-center gap-1.5 p-1 bg-sand-100 rounded-xl overflow-x-auto">
                        <button @click="resourcesFilter = 'all'"
                                :class="resourcesFilter === 'all' ? 'bg-white text-ink-900 shadow-sm font-semibold' : 'text-ink-600 hover:text-ink-900'"
                                class="px-3 py-1.5 rounded-lg text-xs font-heading transition shrink-0">
                            Все
                        </button>
                        <button @click="resourcesFilter = 'downloaded'"
                                :class="resourcesFilter === 'downloaded' ? 'bg-white text-emerald-800 shadow-sm font-semibold' : 'text-ink-600 hover:text-ink-900'"
                                class="px-3 py-1.5 rounded-lg text-xs font-heading transition shrink-0">
                            💾 Скачано офлайн
                        </button>
                        <button @click="resourcesFilter = 'external'"
                                :class="resourcesFilter === 'external' ? 'bg-white text-sky-800 shadow-sm font-semibold' : 'text-ink-600 hover:text-ink-900'"
                                class="px-3 py-1.5 rounded-lg text-xs font-heading transition shrink-0">
                            🔗 Внешние CDN
                        </button>
                        <button @click="resourcesFilter = 'deactivated'"
                                :class="resourcesFilter === 'deactivated' ? 'bg-white text-ink-800 shadow-sm font-semibold' : 'text-ink-600 hover:text-ink-900'"
                                class="px-3 py-1.5 rounded-lg text-xs font-heading transition shrink-0">
                            🚫 Ссылки
                        </button>
                    </div>

                    <div class="relative flex-1 max-w-xs">
                        <input type="text"
                               x-model="resourcesSearch"
                               placeholder="Поиск по домену..."
                               class="w-full bg-sand-50 border border-sand-200 rounded-xl px-3.5 py-2 text-xs text-ink-900 placeholder-ink-400 focus:outline-none focus:border-brand">
                    </div>
                </div>

                <!-- Domains Grouped List -->
                <div class="space-y-3">
                    <template x-for="item in filteredResourcesDomains" :key="item.domain">
                        <div class="border border-sand-200 rounded-2xl p-4 bg-white hover:border-sand-300 transition space-y-3 shadow-xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0"
                                          :class="{
                                              'bg-emerald-500': item.status === 'downloaded',
                                              'bg-sky-500': item.status === 'external',
                                              'bg-ink-400': item.status === 'deactivated'
                                          }"></span>
                                    <span class="font-mono text-base font-bold text-ink-900 truncate" x-text="item.domain"></span>
                                    <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold tracking-wide shrink-0"
                                          :class="{
                                              'bg-emerald-100 text-emerald-800': item.status === 'downloaded',
                                              'bg-sky-100 text-sky-800': item.status === 'external',
                                              'bg-sand-100 text-ink-600': item.status === 'deactivated'
                                          }"
                                          x-text="item.status_label"></span>
                                </div>

                                <div class="flex items-center gap-2 shrink-0 flex-wrap">
                                    <template x-for="(count, typeName) in item.types" :key="typeName">
                                        <span class="px-2 py-0.5 rounded bg-sand-100 border border-sand-200 text-ink-600 text-[11px] font-mono">
                                            <span class="font-semibold text-ink-900" x-text="count"></span> <span x-text="typeName"></span>
                                        </span>
                                    </template>
                                    <span class="text-xs font-semibold font-mono text-ink-400 bg-sand-50 px-2 py-0.5 rounded" x-text="'Всего: ' + item.count"></span>
                                </div>
                            </div>

                            <!-- Expandable URL list toggle -->
                            <div x-data="{ expanded: false }">
                                <button @click="expanded = !expanded"
                                        class="text-xs font-semibold text-brand hover:text-brand-hover flex items-center gap-1 transition">
                                    <span x-text="expanded ? 'Скрыть URL-адреса ▲' : 'Показать примеры URL (' + item.urls.length + ') ▼'"></span>
                                </button>

                                <div x-show="expanded" x-cloak class="mt-2.5 bg-sand-50 border border-sand-200 rounded-xl p-3 space-y-1.5 max-h-48 overflow-y-auto">
                                    <template x-for="url in item.urls" :key="url">
                                        <div class="text-xs font-mono text-ink-700 truncate select-all hover:text-ink-900" :title="url" x-text="url"></div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="filteredResourcesDomains.length === 0">
                        <div class="text-center py-10 text-ink-400 text-sm">
                            Ресурсов по выбранным критериям не найдено.
                        </div>
                    </template>
                </div>
            </div>

            <!-- Footer -->
            <div class="pt-4 border-t border-sand-200 flex items-center justify-between">
                <button @click="fetchResources(resourcesModalSite.id, true)"
                        class="px-4 py-2 bg-sand-100 hover:bg-sand-200 text-ink-600 rounded-xl text-xs font-heading font-semibold transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Пересканировать ресурсы
                </button>
                <button @click="resourcesModalSite = null" class="px-5 py-2.5 bg-sand-100 hover:bg-sand-200 text-ink-900 rounded-xl text-sm font-heading font-semibold transition">
                    Закрыть
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="siteToDelete !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink-900/40 backdrop-blur-sm">
        <div @click.away="siteToDelete = null" class="bg-white border border-sand-200 rounded-2xl max-w-md w-full p-7 shadow-2xl space-y-4">
            <h3 class="text-lg font-bold font-heading text-ink-900">Удаление сайта</h3>
            <p class="text-sm text-ink-600 leading-relaxed">
                Удалить <code class="font-mono text-ink-900 bg-sand-100 px-1.5 py-0.5 rounded font-semibold" x-text="siteToDelete ? siteToDelete.id : ''"></code>? Все локальные файлы и ассеты будут стёрты с диска.
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button @click="siteToDelete = null" class="px-4 py-2.5 bg-sand-100 hover:bg-sand-200 text-ink-600 rounded-xl text-sm font-heading font-medium transition">
                    Отмена
                </button>
                <button @click="executeDelete()" class="px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-sm font-heading font-semibold transition shadow-sm">
                    Удалить
                </button>
            </div>
        </div>
    </div>

    <!-- Duplicate Warning Modal -->
    <div x-show="duplicateModal !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink-900/40 backdrop-blur-sm">
        <div @click.away="duplicateModal = null" class="bg-white border border-sand-200 rounded-2xl max-w-md w-full p-7 shadow-2xl space-y-4">
            <h3 class="text-lg font-bold font-heading text-ink-900">Сайт уже скачан</h3>
            <p class="text-sm text-ink-600 leading-relaxed">
                Этот сайт уже есть в локальном архиве:
                <span class="block font-mono text-brand font-semibold mt-1.5 truncate text-base" x-text="duplicateModal ? duplicateModal.id : ''"></span>
            </p>

            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button @click="duplicateModal = null" class="px-3.5 py-2.5 bg-sand-100 hover:bg-sand-200 text-ink-600 rounded-xl text-sm font-heading font-medium transition">
                    Отмена
                </button>
                <a :href="'/' + routePrefix + '/view/' + (duplicateModal ? duplicateModal.id : '')" target="_blank"
                   @click="duplicateModal = null"
                   class="px-4 py-2.5 bg-brand hover:bg-brand-hover text-white rounded-xl text-sm font-heading font-semibold transition">
                    Открыть
                </a>
                <button @click="startDownload(duplicateModal.url, true); duplicateModal = null;"
                        class="px-4 py-2.5 bg-sand-200 hover:bg-sand-300 text-ink-900 rounded-xl text-sm font-heading font-medium transition">
                    Перезакачать
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-sand-200 bg-white py-6 text-center text-sm text-ink-400 mt-auto">
        <span>Site Cloner — Локальный автономный зеркализатор сайтов</span>
    </footer>

    <!-- Alpine.js Application Logic -->
    <script>
        function siteClonerApp() {
            return {
                routePrefix: '{{ $routePrefix }}',
                inputUrl: '',
                downloadMode: 'light',
                isSubmitting: false,
                searchQuery: '',
                sortBy: 'latest',
                viewMode: 'grid',
                siteToDelete: null,
                duplicateModal: null,
                pagesModalSite: null,
                pagesSearchQuery: '',
                resourcesModalSite: null,
                resourcesData: null,
                isLoadingResources: false,
                resourcesFilter: 'all',
                resourcesSearch: '',
                sites: @json(array_map(fn($s) => $s->toArray(), $sites)),
                totalSizeFormatted: '{{ $totalSizeFormatted }}',

                init() {
                    this.sites.forEach(site => {
                        if (site.status === 'downloading' || site.status === 'pending') {
                            this.connectDownload(site.id);
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

                get filteredResourcesDomains() {
                    if (!this.resourcesData || !this.resourcesData.domains) return [];
                    let domains = [...this.resourcesData.domains];
                    if (this.resourcesFilter !== 'all') {
                        domains = domains.filter(d => d.status === this.resourcesFilter);
                    }
                    if (this.resourcesSearch.trim()) {
                        const q = this.resourcesSearch.toLowerCase();
                        domains = domains.filter(d =>
                            d.domain.toLowerCase().includes(q) ||
                            (d.urls && d.urls.some(u => u.toLowerCase().includes(q)))
                        );
                    }
                    return domains;
                },

                async openResourcesModal(site) {
                    this.resourcesModalSite = site;
                    this.resourcesFilter = 'all';
                    this.resourcesSearch = '';
                    await this.fetchResources(site.id, false);
                },

                async fetchResources(siteId, refresh = false) {
                    this.isLoadingResources = true;
                    try {
                        const res = await fetch(`/${this.routePrefix}/resources/${siteId}${refresh ? '?refresh=1' : ''}`);
                        if (res.ok) {
                            this.resourcesData = await res.json();
                        } else {
                            alert('Не удалось загрузить аудит ресурсов');
                        }
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.isLoadingResources = false;
                    }
                },

                formatSeconds(secs) {
                    if (!secs) return '00:00';
                    const m = Math.floor(secs / 60).toString().padStart(2, '0');
                    const s = (secs % 60).toString().padStart(2, '0');
                    return `${m}:${s}`;
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
                            body: JSON.stringify({ url: url, force: force, mode: this.downloadMode })
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

                        const siteId = data.id;
                        const initialManifest = data.manifest || {};

                        // Immediately insert or update this site directly into this.sites (appears as the first card in the archive)
                        const initialSite = {
                            id: siteId,
                            url: url,
                            files_count: initialManifest.files_count || 0,
                            pages_count: initialManifest.pages_count || 0,
                            total_bytes: initialManifest.total_bytes || 0,
                            human_size: initialManifest.human_size || '0 B',
                            current_file: 'Подключение к серверу...',
                            elapsed_seconds: 0,
                            started_at: initialManifest.started_at || new Date().toISOString(),
                            download_mode: this.downloadMode,
                            ...initialManifest,
                            status: 'downloading'
                        };

                        const existingIdx = this.sites.findIndex(s => s.id === siteId);
                        if (existingIdx >= 0) {
                            this.sites[existingIdx] = initialSite;
                        } else {
                            this.sites.unshift(initialSite);
                        }
                        this.recalculateTotalSize();

                        this.connectDownload(siteId);
                    } catch (err) {
                        alert('Ошибка запроса: ' + err.message);
                    }
                },

                connectDownload(siteId) {
                    // Local ticker for smooth elapsed seconds updates
                    const timerInterval = setInterval(() => {
                        const idx = this.sites.findIndex(s => s.id === siteId);
                        if (idx === -1) {
                            clearInterval(timerInterval);
                            return;
                        }
                        if (this.sites[idx].status !== 'downloading' && this.sites[idx].status !== 'pending') {
                            clearInterval(timerInterval);
                            return;
                        }
                        this.sites[idx].elapsed_seconds = (this.sites[idx].elapsed_seconds || 0) + 1;
                    }, 1000);

                    const updateSiteData = (freshData) => {
                        const idx = this.sites.findIndex(s => s.id === siteId);
                        if (idx >= 0) {
                            // Merge fresh data reactively into this.sites
                            this.sites[idx] = { ...this.sites[idx], ...freshData };
                            this.recalculateTotalSize();
                            return this.sites[idx];
                        }
                        return null;
                    };

                    // Fallback polling (every 800ms) ensuring continuous updates even if SSE is buffered
                    const pollInterval = setInterval(async () => {
                        const currentSite = this.sites.find(s => s.id === siteId);
                        if (!currentSite || currentSite.status === 'completed' || currentSite.status === 'failed') {
                            clearInterval(pollInterval);
                            clearInterval(timerInterval);
                            return;
                        }

                        try {
                            const res = await fetch(`/${this.routePrefix}/status/${siteId}`);
                            if (res.ok) {
                                const data = await res.json();
                                const updated = updateSiteData(data);
                                if (updated && (updated.status === 'completed' || updated.status === 'failed')) {
                                    clearInterval(pollInterval);
                                    clearInterval(timerInterval);
                                    if (evtSource) evtSource.close();
                                }
                            }
                        } catch (e) {
                            // silent
                        }
                    }, 800);

                    // Real-time EventSource connection
                    let evtSource = null;
                    try {
                        evtSource = new EventSource(`/${this.routePrefix}/progress/${siteId}`);

                        evtSource.onmessage = (e) => {
                            try {
                                const freshData = JSON.parse(e.data);
                                const updated = updateSiteData(freshData);

                                if (updated && (updated.status === 'completed' || updated.status === 'failed')) {
                                    clearInterval(pollInterval);
                                    clearInterval(timerInterval);
                                    evtSource.close();
                                }
                            } catch (err) {
                                console.error('SSE JSON error', err);
                            }
                        };

                        evtSource.addEventListener('done', () => {
                            clearInterval(pollInterval);
                            clearInterval(timerInterval);
                            evtSource.close();
                        });

                        evtSource.onerror = (err) => {
                            console.warn('SSE stream notice, fallback polling active', err);
                        };
                    } catch (e) {
                        console.warn('EventSource init error, relying on polling', e);
                    }
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
