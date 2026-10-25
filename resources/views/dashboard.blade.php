<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Site Cloner — Локальный архив сайтов</title>

    <!-- Typography: Space Grotesk & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

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
                            DEFAULT: '#dd4814',
                            hover: '#c83e0e',
                            subtle: '#2a1610',
                            border: '#542010'
                        },
                        stone: {
                            950: '#0e0e10',
                            900: '#18181b',
                            850: '#202024',
                            800: '#27272a',
                            700: '#3f3f46',
                            400: '#a1a1aa',
                            300: '#d4d4d8',
                            100: '#f4f4f5'
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
            background-color: #0e0e10;
            color: #d4d4d8;
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, .font-heading {
            font-family: 'Space Grotesk', sans-serif;
        }
        ::selection {
            background: #dd4814;
            color: #ffffff;
        }
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0e0e10;
        }
        ::-webkit-scrollbar-thumb {
            background: #27272a;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #3f3f46;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col" x-data="siteClonerApp()" x-init="init()">

    <!-- Top Bar -->
    <header class="border-b border-stone-800 bg-stone-950/90 sticky top-0 z-40 backdrop-blur">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('site-cloner.index') }}" class="flex items-center gap-2.5 group">
                    <span class="w-3 h-3 rounded-full bg-brand group-hover:scale-110 transition-transform"></span>
                    <span class="text-base font-semibold font-heading text-stone-100 tracking-tight">Site Cloner</span>
                </a>
                <span class="text-xs text-stone-700 select-none">/</span>
                <span class="text-xs text-stone-400">Локальный архив</span>
            </div>

            <!-- Header metrics -->
            <div class="flex items-center gap-5 text-xs">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-stone-400">Сайтов:</span>
                    <span class="font-semibold text-stone-100 font-heading" x-text="sites.length"></span>
                </div>
                <div class="w-px h-3.5 bg-stone-800"></div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-stone-400">Объем:</span>
                    <span class="font-semibold text-stone-100 font-heading" x-text="totalSizeFormatted"></span>
                </div>
                <div class="w-px h-3.5 bg-stone-800"></div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-stone-400">Файлов:</span>
                    <span class="font-semibold text-stone-100 font-heading" x-text="totalFilesCount"></span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8 flex-1 w-full space-y-8">

        <!-- Download Input Workbench -->
        <section class="bg-stone-900 border border-stone-800 rounded-xl p-6 sm:p-7 shadow-sm">
            <div class="max-w-3xl space-y-3">
                <h1 class="text-xl sm:text-2xl font-semibold font-heading text-stone-100 tracking-tight">
                    Зеркалирование веб-сайта
                </h1>
                <p class="text-sm text-stone-400 leading-relaxed">
                    Введите URL страницы или раздела. Движок скачает все HTML-страницы внутри этого пути, подтянет стили, скрипты, шрифты и иконки для автономной работы.
                </p>

                <form @submit.prevent="submitUrl()" class="pt-2 space-y-3">
                    <div class="flex flex-col sm:flex-row gap-2.5">
                        <div class="relative flex-1">
                            <input type="url"
                                   x-model="inputUrl"
                                   placeholder="https://bootstrapmade.com/content/demo/Horizon/"
                                   required
                                   class="w-full bg-stone-950 border border-stone-800 rounded-lg px-3.5 py-2.5 text-sm text-stone-100 placeholder-stone-400 focus:outline-none focus:border-brand focus:ring-1 focus:ring-brand transition">
                        </div>

                        <button type="submit"
                                :disabled="isSubmitting"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-brand hover:bg-brand-hover disabled:opacity-50 text-white rounded-lg text-sm font-medium transition shrink-0 cursor-pointer">
                            <svg x-show="isSubmitting" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span x-text="isSubmitting ? 'Проверка...' : 'Скачать сайт'"></span>
                        </button>
                    </div>

                    <!-- Clear, useful guidance notes (no decorative jargon) -->
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs text-stone-400 pt-0.5">
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Скачиваются только страницы внутри указанного пути
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand"></span>
                            Шрифты, CSS и JS сохраняются полностью
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-stone-500"></span>
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
                 class="space-y-3">

            <div class="flex items-center justify-between text-xs text-stone-400">
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-brand animate-pulse"></span>
                    <span class="font-medium text-stone-200">Текущие загрузки (<span x-text="activeDownloads.length"></span>)</span>
                </div>
                <button @click="dismissAllCompleted()" x-show="hasCompletedTasks()" class="text-stone-400 hover:text-stone-200 transition">
                    Очистить завершенные
                </button>
            </div>

            <div class="space-y-2.5">
                <template x-for="task in activeDownloads" :key="task.id">
                    <div class="bg-stone-900 border rounded-lg p-4 transition-colors"
                         :class="task.status === 'completed' ? 'border-emerald-500/40' : (task.status === 'failed' ? 'border-rose-500/40' : 'border-stone-800')">

                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-2.5">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-semibold text-stone-200 truncate" x-text="task.id"></span>
                                    <span class="text-[11px] px-2 py-0.5 rounded text-xs font-medium"
                                          :class="task.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400' : (task.status === 'failed' ? 'bg-rose-500/10 text-rose-400' : 'bg-brand/10 text-brand')">
                                        <span x-text="task.status === 'completed' ? 'Завершено' : (task.status === 'failed' ? 'Ошибка' : 'Загрузка')"></span>
                                    </span>
                                </div>
                                <span class="text-xs text-stone-400 truncate block mt-0.5" x-text="task.url"></span>
                            </div>

                            <div class="flex items-center gap-4 text-xs font-mono shrink-0">
                                <span class="text-stone-300" x-text="formatSeconds(task.elapsed_seconds)"></span>
                                <span class="text-stone-400" x-text="(task.files_count || 0) + ' файлов'"></span>
                                <span class="text-brand font-semibold" x-text="task.human_size || '0 B'"></span>

                                <button @click="dismissTask(task.id)" class="text-stone-500 hover:text-stone-300 transition text-sm leading-none p-1" title="Скрыть">
                                    ✕
                                </button>
                            </div>
                        </div>

                        <!-- Current processing file indicator -->
                        <div class="flex items-center gap-2 text-xs bg-stone-950 px-3 py-1.5 rounded border border-stone-850">
                            <span class="text-stone-500 shrink-0">Файл:</span>
                            <span class="font-mono text-stone-300 truncate" x-text="task.current_file || 'Инициализация...' "></span>
                        </div>

                        <!-- Action button if completed -->
                        <div x-show="task.status === 'completed'" class="mt-2.5 pt-2 border-t border-stone-800 flex items-center justify-between text-xs">
                            <span class="text-emerald-400">Сайт добавлен в архив и готов к просмотру</span>
                            <a :href="'/' + routePrefix + '/view/' + task.id" target="_blank"
                               class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded text-xs font-medium transition">
                                <span>Открыть</span>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- Archive Section -->
        <section class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-stone-800 pb-3">
                <h2 class="text-base font-semibold font-heading text-stone-100">
                    Архив сайтов
                </h2>

                <div class="flex items-center gap-2.5 w-full sm:w-auto">
                    <!-- Search -->
                    <div class="relative flex-1 sm:w-56">
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Поиск по архиву..."
                               class="w-full bg-stone-900 border border-stone-800 rounded-md px-3 py-1.5 text-xs text-stone-100 placeholder-stone-400 focus:outline-none focus:border-stone-700">
                    </div>

                    <!-- Sort -->
                    <select x-model="sortBy" class="bg-stone-900 border border-stone-800 rounded-md px-2.5 py-1.5 text-xs text-stone-300 focus:outline-none focus:border-stone-700">
                        <option value="latest">По дате</option>
                        <option value="size">По размеру</option>
                        <option value="name">По имени</option>
                    </select>
                </div>
            </div>

            <!-- Empty Archive -->
            <template x-if="filteredSites.length === 0">
                <div class="bg-stone-900 border border-stone-800 rounded-xl p-12 text-center space-y-2">
                    <p class="text-sm text-stone-300">В архиве пока нет сайтов</p>
                    <p class="text-xs text-stone-400">Вставьте адрес сайта в форму выше, чтобы сохранить первую локальную копию.</p>
                </div>
            </template>

            <!-- Sites Grid -->
            <div x-show="filteredSites.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="site in filteredSites" :key="site.id">
                    <div class="bg-stone-900 border border-stone-800 rounded-lg p-4 flex flex-col justify-between hover:border-stone-700 transition space-y-3.5">

                        <div class="space-y-2.5">
                            <div>
                                <h3 class="text-sm font-semibold font-heading text-stone-100 truncate" :title="site.id" x-text="site.id"></h3>
                                <a :href="site.url" target="_blank" class="text-xs text-stone-400 hover:text-stone-300 truncate block mt-0.5" :title="site.url" x-text="site.url"></a>
                            </div>

                            <!-- Data metrics row -->
                            <div class="grid grid-cols-3 gap-1.5 bg-stone-950 p-2.5 rounded border border-stone-850 text-center">
                                <div>
                                    <span class="text-[11px] text-stone-400 block">Размер</span>
                                    <span class="text-xs font-semibold font-mono text-stone-200" x-text="site.human_size"></span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-stone-400 block">Файлов</span>
                                    <span class="text-xs font-semibold font-mono text-stone-200" x-text="site.files_count"></span>
                                </div>
                                <div>
                                    <span class="text-[11px] text-stone-400 block">Страниц</span>
                                    <span class="text-xs font-semibold font-mono text-stone-200" x-text="site.pages_count"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Card action buttons -->
                        <div class="flex items-center gap-2 pt-2 border-t border-stone-800/80">
                            <!-- Open in viewer -->
                            <a :href="'/' + routePrefix + '/view/' + site.id" target="_blank"
                               class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-brand hover:bg-brand-hover text-white rounded text-xs font-medium transition">
                                <span>Открыть сайт</span>
                            </a>

                            <!-- Inspect pages modal -->
                            <button @click="openPagesModal(site)"
                                    class="px-2.5 py-1.5 bg-stone-950 hover:bg-stone-800 border border-stone-800 text-stone-300 rounded text-xs transition"
                                    title="Список сохраненных страниц">
                                Страницы
                            </button>

                            <!-- Delete -->
                            <button @click="confirmDelete(site)"
                                    class="p-1.5 text-stone-400 hover:text-rose-400 hover:bg-rose-950/20 rounded transition"
                                    title="Удалить">
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

    <!-- Pages List Modal -->
    <div x-show="pagesModalSite !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/80 backdrop-blur-sm">
        <div @click.away="pagesModalSite = null" class="bg-stone-900 border border-stone-800 rounded-xl max-w-lg w-full p-5 shadow-2xl space-y-3.5 max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between pb-2.5 border-b border-stone-800">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold font-heading text-stone-100 truncate" x-text="'Страницы: ' + (pagesModalSite ? pagesModalSite.id : '')"></h3>
                    <p class="text-xs text-stone-400">Выберите страницу для открытия</p>
                </div>
                <button @click="pagesModalSite = null" class="text-stone-400 hover:text-stone-200 text-sm leading-none p-1">
                    ✕
                </button>
            </div>

            <div class="relative">
                <input type="text"
                       x-model="pagesSearchQuery"
                       placeholder="Фильтр страниц..."
                       class="w-full bg-stone-950 border border-stone-800 rounded px-3 py-1.5 text-xs text-stone-100 placeholder-stone-400 focus:outline-none focus:border-brand">
            </div>

            <div class="overflow-y-auto space-y-1 flex-1 pr-1">
                <template x-for="page in filteredModalPages" :key="page">
                    <a :href="'/' + routePrefix + '/view/' + pagesModalSite.id + '/' + page" target="_blank"
                       class="flex items-center justify-between p-2 rounded bg-stone-950 hover:bg-stone-800 border border-stone-850 text-xs text-stone-300 hover:text-white transition">
                        <span class="font-mono truncate" x-text="page"></span>
                        <span class="text-brand text-xs font-medium shrink-0 ml-2">Открыть</span>
                    </a>
                </template>
                <template x-if="filteredModalPages.length === 0">
                    <p class="text-xs text-stone-400 text-center py-6">Страниц не найдено.</p>
                </template>
            </div>

            <div class="pt-2.5 border-t border-stone-800 flex justify-end">
                <button @click="pagesModalSite = null" class="px-3.5 py-1.5 bg-stone-800 hover:bg-stone-700 text-stone-200 rounded text-xs transition">
                    Закрыть
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="siteToDelete !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/80 backdrop-blur-sm">
        <div @click.away="siteToDelete = null" class="bg-stone-900 border border-stone-800 rounded-xl max-w-sm w-full p-5 shadow-2xl space-y-3">
            <h3 class="text-base font-semibold font-heading text-stone-100">Удаление сайта</h3>
            <p class="text-xs text-stone-300 leading-relaxed">
                Удалить <code class="font-mono text-stone-100 bg-stone-950 px-1 py-0.5 rounded" x-text="siteToDelete ? siteToDelete.id : ''"></code>? Все локальные файлы и ассеты будут удалены.
            </p>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button @click="siteToDelete = null" class="px-3 py-1.5 bg-stone-800 hover:bg-stone-700 text-stone-300 rounded text-xs transition">
                    Отмена
                </button>
                <button @click="executeDelete()" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded text-xs font-medium transition">
                    Удалить
                </button>
            </div>
        </div>
    </div>

    <!-- Duplicate Warning Modal -->
    <div x-show="duplicateModal !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-950/80 backdrop-blur-sm">
        <div @click.away="duplicateModal = null" class="bg-stone-900 border border-stone-800 rounded-xl max-w-sm w-full p-5 shadow-2xl space-y-3">
            <h3 class="text-base font-semibold font-heading text-stone-100">Сайт уже скачан</h3>
            <p class="text-xs text-stone-300 leading-relaxed">
                Этот сайт уже есть в архиве:
                <span class="block font-mono text-brand mt-1 truncate" x-text="duplicateModal ? duplicateModal.id : ''"></span>
            </p>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button @click="duplicateModal = null" class="px-3 py-1.5 bg-stone-800 hover:bg-stone-700 text-stone-300 rounded text-xs transition">
                    Отмена
                </button>
                <a :href="'/' + routePrefix + '/view/' + (duplicateModal ? duplicateModal.id : '')" target="_blank"
                   @click="duplicateModal = null"
                   class="px-3 py-1.5 bg-brand hover:bg-brand-hover text-white rounded text-xs font-medium transition">
                    Открыть
                </a>
                <button @click="startDownload(duplicateModal.url, true); duplicateModal = null;"
                        class="px-3 py-1.5 bg-stone-800 hover:bg-stone-700 text-stone-200 border border-stone-700 rounded text-xs transition">
                    Перезакачать
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-stone-800/60 py-6 text-center text-xs text-stone-400 mt-auto">
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
