<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('site-cloner::common.not_found.title') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center p-4 font-sans">
    <div class="max-w-md w-full bg-slate-800/80 border border-slate-700/80 rounded-2xl p-6 shadow-2xl text-center backdrop-blur-sm">
        <div class="w-16 h-16 bg-amber-500/10 text-amber-400 rounded-full flex items-center justify-center mx-auto mb-4 border border-amber-500/20">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-white mb-2">{{ __('site-cloner::common.not_found.heading') }}</h1>
        <p class="text-sm text-slate-400 mb-4">
            {{ __('site-cloner::common.not_found.message', ['path' => $path]) }}
        </p>

        @if($manifest && !empty($manifest->pages))
            <div class="mb-6 text-left bg-slate-900/60 p-3 rounded-xl border border-slate-700/50 max-h-40 overflow-y-auto">
                <span class="text-xs uppercase font-semibold text-slate-400 block mb-2">{{ __('site-cloner::common.not_found.available_pages') }}</span>
                <ul class="text-xs space-y-1.5">
                    @foreach($manifest->pages as $page)
                        <li>
                            <a href="{{ route('site-cloner.view', ['siteId' => $siteId, 'path' => $page]) }}" class="text-indigo-400 hover:text-indigo-300 hover:underline flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ $page }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center justify-center gap-3">
            <a href="{{ route('site-cloner.view', ['siteId' => $siteId]) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-medium transition shadow-lg shadow-indigo-600/20">
                {{ __('site-cloner::common.not_found.btn_home') }}
            </a>
            <a href="{{ route('site-cloner.index') }}" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg text-sm font-medium transition">
                {{ __('site-cloner::common.not_found.btn_dashboard') }}
            </a>
        </div>
    </div>
</body>
</html>
