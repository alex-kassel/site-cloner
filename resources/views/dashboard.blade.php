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
                                   placeholder="https://bootstrapmade.com/content/demo/Horizon/"
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

        <!-- Active Downloads Strip (Self-dismissing on completion) -->
        <section x-show="activeDownloads.length > 0"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 x-cloak
                 class="space-y-3.5">

            <div class="flex items-center justify-between text-sm text-ink-600">
                <div class="flex items-center gap-2.5">
                    <span class="inline-block w-3 h-3 rounded-full bg-brand animate-pulse"></span>
                    <span class="font-bold font-heading text-ink-900 text-base">Идет загрузка (<span x-text="activeDownloads.length"></span>)</span>
                </div>
                <button @click="dismissAllCompleted()" x-show="hasCompletedTasks()" class="text-sm text-ink-400 hover:text-ink-900 transition font-medium">
                    Очистить завершенные
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="task in activeDownloads" :key="task.id">
                    <div class="bg-white border rounded-xl p-5 shadow-sm transition-colors"
                         :class="task.status === 'completed' ? 'border-emerald-300 bg-emerald-50/30' : (task.status === 'failed' ? 'border-rose-300 bg-rose-50/30' : 'border-brand-200')">

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3.5">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2.5 mb-1.5 flex-wrap">
                                    <span class="font-mono text-sm font-semibold text-ink-900 bg-sand-100 px-3 py-1 rounded-md border border-sand-200 truncate" x-text="task.id"></span>
                                    <span class="text-xs sm:text-sm px-3 py-0.5 rounded-full font-heading font-semibold tracking-wide"
                                          :class="task.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : (task.status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-brand-100 text-brand')">
                                        <span x-text="task.status === 'completed' ? '✓ Завершено' : (task.status === 'failed' ? 'Ошибка' : 'Скачивание...')"></span>
                                    </span>
                                </div>
                                <span class="text-sm text-ink-400 truncate block" x-text="task.url"></span>
                            </div>

                            <div class="flex items-center gap-5 text-sm font-mono shrink-0">
                                <span class="text-ink-900 font-bold text-base" x-text="formatSeconds(task.elapsed_seconds)"></span>
                                <span class="text-ink-600" x-text="(task.files_count || 0) + ' файлов'"></span>
                                <span class="text-brand font-bold text-base" x-text="task.human_size || '0 B'"></span>

                                <button @click="dismissTask(task.id)" class="text-ink-400 hover:text-ink-900 transition text-base leading-none p-1.5" title="Скрыть">
                                    ✕
                                </button>
                            </div>
                        </div>

                        <!-- Current file ticker -->
                        <div class="flex items-center gap-2.5 text-sm bg-sand-50 px-3.5 py-2.5 rounded-lg border border-sand-200">
                            <span class="text-ink-400 shrink-0 font-medium">Файл:</span>
                            <span class="font-mono text-ink-900 truncate" x-text="task.current_file || 'Инициализация...' "></span>
                        </div>

                        <!-- Completed State Actions -->
                        <div x-show="task.status === 'completed'" class="mt-3.5 pt-3.5 border-t border-sand-200 flex items-center justify-between text-sm">
                            <span class="text-emerald-700 font-semibold">✓ Сайт успешно скачан и добавлен в архив</span>
                            <a :href="'/' + routePrefix + '/view/' + task.id" target="_blank"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-heading font-semibold transition">
                                <span>Открыть сайт</span>
                            </a>
                        </div>
                    </div>
                </template>
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

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <!-- Search -->
                    <div class="relative flex-1 sm:w-64">
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Поиск по сайтам..."
                               class="w-full bg-white border border-sand-300 rounded-lg px-3.5 py-2 text-sm text-ink-900 placeholder-ink-400 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand-100">
                    </div>

                    <!-- Sort -->
                    <select x-model="sortBy" class="bg-white border border-sand-300 rounded-lg px-3.5 py-2 text-sm text-ink-600 focus:outline-none focus:border-brand">
                        <option value="latest">По дате</option>
                        <option value="size">По размеру</option>
                        <option value="name">По имени</option>
                    </select>
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
            <div x-show="filteredSites.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="site in filteredSites" :key="site.id">
                    <div class="bg-white border border-sand-200 rounded-2xl p-6 flex flex-col justify-between hover:border-brand-200 hover:shadow-md transition duration-200 space-y-4 shadow-sm group">

                        <div class="space-y-3.5">
                            <div class="flex items-start justify-between gap-2.5">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base sm:text-lg font-bold font-heading text-ink-900 truncate" :title="site.id" x-text="site.id"></h3>
                                    <a :href="site.url" target="_blank" class="text-sm text-ink-400 hover:text-brand truncate block mt-0.5" :title="site.url" x-text="site.url"></a>
                                </div>
                                <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                                    Готов
                                </span>
                            </div>

                            <!-- Metrics row -->
                            <div class="grid grid-cols-3 gap-2.5 bg-sand-50 p-3.5 rounded-xl border border-sand-200 text-center">
                                <div>
                                    <span class="text-xs text-ink-400 block font-medium">Размер</span>
                                    <span class="text-sm sm:text-base font-bold font-mono text-brand" x-text="site.human_size"></span>
                                </div>
                                <div>
                                    <span class="text-xs text-ink-400 block font-medium">Файлов</span>
                                    <span class="text-sm sm:text-base font-bold font-mono text-ink-900" x-text="site.files_count"></span>
                                </div>
                                <div>
                                    <span class="text-xs text-ink-400 block font-medium">Страниц</span>
                                    <span class="text-sm sm:text-base font-bold font-mono text-emerald-700" x-text="site.pages_count"></span>
                                </div>
                            </div>

                            <div class="text-xs sm:text-sm text-ink-400 flex items-center justify-between">
                                <span>Скачан:</span>
                                <span class="font-mono text-ink-600" x-text="site.completed_at || site.started_at || 'неизвестно'"></span>
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="flex items-center gap-2.5 pt-3.5 border-t border-sand-200">
                            <!-- Open in viewer -->
                            <a :href="'/' + routePrefix + '/view/' + site.id" target="_blank"
                               class="flex-1 inline-flex items-center justify-center gap-1.5 px-4 py-2.5 bg-brand hover:bg-brand-hover text-white rounded-xl text-sm font-heading font-semibold transition shadow-sm shadow-brand/20">
                                <span>Открыть сайт</span>
                            </a>

                            <!-- Inspect pages modal -->
                            <button @click="openPagesModal(site)"
                                    class="px-3.5 py-2.5 bg-sand-50 hover:bg-sand-100 border border-sand-200 text-ink-600 rounded-xl text-sm font-heading font-semibold transition"
                                    title="Список страниц">
                                Страницы
                            </button>

                            <!-- Delete -->
                            <button @click="confirmDelete(site)"
                                    class="p-2.5 text-ink-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition"
                                    title="Удалить">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
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
                            current_file: 'Подключение...',
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

                                // Auto-dismiss completed download task after 3.5 seconds
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
