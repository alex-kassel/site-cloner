<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Site Cloner Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.1); opacity: 0.4; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }
        .pulse-activity {
            animation: pulse-ring 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen font-sans antialiased selection:bg-indigo-500 selection:text-white"
      x-data="siteClonerApp()"
      x-init="init()">

    <!-- Top Navigation -->
    <header class="border-b border-slate-800 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </div>
                <div>
                    <h1 class="font-bold text-lg text-white leading-tight">Site Cloner</h1>
                    <p class="text-xs text-slate-400">Локальный зеркализатор и автономный просмотрщик сайтов</p>
                </div>
            </div>

            <div class="flex items-center gap-4 text-xs">
                <div class="bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700/60 flex items-center gap-2">
                    <span class="text-slate-400">Сайтов в архиве:</span>
                    <span class="font-semibold text-white" x-text="sites.length"></span>
                </div>
                <div class="bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700/60 flex items-center gap-2">
                    <span class="text-slate-400">Общий вес:</span>
                    <span class="font-semibold text-indigo-400" x-text="totalSizeFormatted"></span>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Add URL Card -->
        <section class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="max-w-3xl">
                <h2 class="text-xl font-bold text-white mb-1">Клонировать новый сайт</h2>
                <p class="text-sm text-slate-400 mb-6">
                    Введите URL страницы или раздела. Все зависимости (CSS, JS, шрифты, иконки, картинки) скачаются локально для 100% автономной работы.
                </p>

                <form @submit.prevent="submitUrl()" class="space-y-4">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                            </div>
                            <input type="url"
                                   x-model="inputUrl"
                                   placeholder="https://bootstrapmade.com/content/demo/AppDashboard/"
                                   required
                                   class="w-full bg-slate-950 border border-slate-700/80 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                        </div>
                        <button type="submit"
                                :disabled="isSubmitting"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white rounded-xl text-sm font-semibold transition shadow-lg shadow-indigo-600/25 shrink-0">
                            <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <svg x-show="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Скачать сайт</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-4 text-xs text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Ограничение пути соблюдается строго
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                            Ассеты скачиваются полностью
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-violet-400"></span>
                            Внешние ссылки деактивируются
                        </span>
                    </div>
                </form>
            </div>
        </section>

        <!-- Active Downloads (Real-time SSE Cards) -->
        <section x-show="activeDownloads.length > 0" x-cloak class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-white flex items-center gap-2">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-indigo-500"></span>
                    </span>
                    Активные процессы скачивания (<span x-text="activeDownloads.length"></span>)
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <template x-for="task in activeDownloads" :key="task.id">
                    <div class="bg-slate-900 border border-indigo-500/30 rounded-xl p-5 shadow-lg relative overflow-hidden flex flex-col justify-between space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-mono font-medium text-indigo-400 bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20 truncate" x-text="task.id"></span>
                                    <span class="text-[11px] px-2 py-0.5 rounded font-medium"
                                          :class="task.status === 'completed' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (task.status === 'failed' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20')"
                                          x-text="task.status === 'completed' ? 'Завершено' : (task.status === 'failed' ? 'Ошибка' : 'Скачивание...')"></span>
                                </div>
                                <a :href="task.url" target="_blank" class="text-xs text-slate-400 hover:text-slate-200 truncate block hover:underline" x-text="task.url"></a>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-xs font-mono text-slate-300 font-semibold" x-text="formatSeconds(task.elapsed_seconds)"></span>
                            </div>
                        </div>

                        <!-- Real-time metrics -->
                        <div class="grid grid-cols-3 gap-2 bg-slate-950/60 p-3 rounded-lg border border-slate-800/80 text-center">
                            <div>
                                <span class="text-[10px] uppercase text-slate-500 block">Файлов</span>
                                <span class="text-sm font-bold text-white" x-text="task.files_count || 0"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase text-slate-500 block">Объем</span>
                                <span class="text-sm font-bold text-indigo-400" x-text="task.human_size || '0 B'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] uppercase text-slate-500 block">Страниц</span>
                                <span class="text-sm font-bold text-emerald-400" x-text="task.pages_count || 0"></span>
                            </div>
                        </div>

                        <!-- Current active file ticker -->
                        <div class="text-xs">
                            <span class="text-slate-500 text-[11px] block mb-1">Текущий файл:</span>
                            <div class="font-mono text-slate-300 bg-slate-950 px-2.5 py-1.5 rounded border border-slate-800/80 truncate flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0" :class="task.status === 'downloading' ? 'pulse-activity' : ''"></span>
                                <span class="truncate" x-text="task.current_file || 'Инициализация...' "></span>
                            </div>
                        </div>

                        <!-- Action buttons when completed -->
                        <div x-show="task.status === 'completed'" class="flex items-center gap-2 pt-1 border-t border-slate-800">
                            <a :href="'/' + routePrefix + '/view/' + task.id" target="_blank"
                               class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>Открыть сайт</span>
                            </a>
                            <button @click="dismissTask(task.id)" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs transition">
                                Скрыть
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- Cloned Sites Archive -->
        <section class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <span>Локальный архив сайтов</span>
                </h2>

                <div class="relative w-full sm:w-64">
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Поиск по сайтам..."
                           class="w-full bg-slate-900 border border-slate-800 rounded-lg pl-8 pr-3 py-1.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <svg class="w-3.5 h-3.5 text-slate-500 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <template x-if="filteredSites.length === 0">
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-12 text-center">
                    <div class="w-12 h-12 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-3 text-slate-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <p class="text-sm text-slate-400">Пока нет ни одного скачанного сайта.</p>
                    <p class="text-xs text-slate-500 mt-1">Вставьте URL в форму сверху, чтобы начать.</p>
                </div>
            </template>

            <div x-show="filteredSites.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="site in filteredSites" :key="site.id">
                    <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 hover:border-slate-700 transition flex flex-col justify-between group shadow-sm">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-xs font-mono font-medium text-slate-300 bg-slate-800/80 px-2 py-1 rounded border border-slate-700/60 truncate" x-text="site.id"></span>
                                <span class="text-[11px] px-2 py-0.5 rounded font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Готов</span>
                            </div>

                            <div>
                                <a :href="site.url" target="_blank" class="text-xs text-slate-400 hover:text-indigo-400 truncate block hover:underline" :title="site.url" x-text="site.url"></a>
                                <span class="text-[11px] text-slate-500 block mt-1" x-text="'Скачан: ' + (site.completed_at || site.started_at || 'неизвестно')"></span>
                            </div>

                            <div class="grid grid-cols-3 gap-2 bg-slate-950/40 p-2.5 rounded-lg border border-slate-800/50 text-center text-xs">
                                <div>
                                    <span class="text-[10px] text-slate-500 block">Размер</span>
                                    <span class="font-semibold text-indigo-400" x-text="site.human_size"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-500 block">Файлов</span>
                                    <span class="font-semibold text-slate-200" x-text="site.files_count"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-slate-500 block">Страниц</span>
                                    <span class="font-semibold text-emerald-400" x-text="site.pages_count"></span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-4 mt-4 border-t border-slate-800/80">
                            <a :href="'/' + routePrefix + '/view/' + site.id" target="_blank"
                               class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600/90 hover:bg-indigo-600 text-white rounded-lg text-xs font-medium transition shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <span>Открыть</span>
                            </a>
                            <button @click="confirmDelete(site)"
                                    class="p-2 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition"
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

    <!-- Delete Confirmation Modal -->
    <div x-show="siteToDelete !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="siteToDelete = null" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center gap-3 text-rose-400">
                <div class="w-10 h-10 rounded-xl bg-rose-500/10 flex items-center justify-center border border-rose-500/20 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="font-bold text-white text-base">Подтверждение удаления</h3>
            </div>

            <p class="text-sm text-slate-300">
                Вы действительно хотите удалить сайт <code class="text-rose-400 font-mono text-xs bg-slate-950 px-1.5 py-0.5 rounded" x-text="siteToDelete ? siteToDelete.id : ''"></code>?
            </p>
            <p class="text-xs text-slate-400">
                Все локальные файлы, стили и скрипты будут безвозвратно стёрты с диска.
            </p>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button @click="siteToDelete = null" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-semibold transition">
                    Отмена
                </button>
                <button @click="executeDelete()" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-lg text-xs font-semibold transition shadow-lg shadow-rose-600/20">
                    Да, удалить
                </button>
            </div>
        </div>
    </div>

    <!-- Duplicate Warning Modal -->
    <div x-show="duplicateModal !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="duplicateModal = null" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center gap-3 text-amber-400">
                <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center border border-amber-500/20 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="font-bold text-white text-base">Сайт уже скачан</h3>
            </div>

            <p class="text-sm text-slate-300">
                Этот URL уже есть в вашем локальном архиве:
                <span class="block font-mono text-xs text-amber-400 bg-slate-950 p-2 rounded mt-1 break-all" x-text="duplicateModal ? duplicateModal.id : ''"></span>
            </p>
            <p class="text-xs text-slate-400">
                Вы можете открыть уже сохраненную копию прямо сейчас или перезакачать её заново с нуля.
            </p>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button @click="duplicateModal = null" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-medium transition">
                    Отмена
                </button>
                <a :href="'/' + routePrefix + '/view/' + (duplicateModal ? duplicateModal.id : '')" target="_blank"
                   @click="duplicateModal = null"
                   class="px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold transition">
                    Открыть сохраненный
                </a>
                <button @click="startDownload(duplicateModal.url, true); duplicateModal = null;"
                        class="px-3 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-semibold transition">
                    Перезакачать
                </button>
            </div>
        </div>
    </div>

    <script>
        function siteClonerApp() {
            return {
                routePrefix: '{{ $routePrefix }}',
                inputUrl: '',
                isSubmitting: false,
                searchQuery: '',
                siteToDelete: null,
                duplicateModal: null,
                activeDownloads: [],
                sites: @json(array_map(fn($s) => $s->toArray(), $sites)),
                totalSizeFormatted: '{{ $totalSizeFormatted }}',

                init() {
                    // Check if any sites in storage are currently in downloading status
                    this.sites.forEach(site => {
                        if (site.status === 'downloading' || site.status === 'pending') {
                            this.connectSSE(site.id, site.url);
                        }
                    });
                },

                get filteredSites() {
                    if (!this.searchQuery.trim()) {
                        return this.sites;
                    }
                    const q = this.searchQuery.toLowerCase();
                    return this.sites.filter(s =>
                        s.id.toLowerCase().includes(q) ||
                        (s.url && s.url.toLowerCase().includes(q))
                    );
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
                        // Check if URL already exists
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
                    // Check if already in activeDownloads
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
                            }
                        } catch (err) {
                            console.error('SSE JSON error', err);
                        }
                    };

                    evtSource.addEventListener('done', () => {
                        evtSource.close();
                    });

                    evtSource.onerror = (err) => {
                        console.warn('SSE connection error, will retry or close', err);
                    };
                },

                refreshSitesList(completedSite) {
                    const idx = this.sites.findIndex(s => s.id === completedSite.id);
                    if (idx >= 0) {
                        this.sites[idx] = completedSite;
                    } else {
                        this.sites.unshift(completedSite);
                    }
                },

                dismissTask(taskId) {
                    this.activeDownloads = this.activeDownloads.filter(t => t.id !== taskId);
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
