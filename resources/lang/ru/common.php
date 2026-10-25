<?php

declare(strict_types=1);

return [
    'title' => 'Site Cloner — Локальный архив сайтов',
    'app_name' => 'Site Cloner',
    'app_subtitle' => 'Локальный архив сайтов',

    'stats_sites' => 'Сайтов:',
    'stats_storage' => 'Объем:',
    'stats_files' => 'Файлов:',

    'footer' => 'Site Cloner — Локальный автономный зеркализатор сайтов',

    'alerts' => [
        'connecting' => 'Подключение к серверу...',
        'audit_failed' => 'Не удалось загрузить аудит ресурсов.',
        'check_failed' => 'Ошибка проверки URL: ',
        'start_failed' => 'Не удалось запустить скачивание.',
        'request_failed' => 'Ошибка запроса: ',
        'delete_failed' => 'Не удалось удалить сайт.',
        'delete_error' => 'Ошибка удаления: ',
    ],

    'not_found' => [
        'title' => 'Файл не найден — Site Cloner',
        'heading' => 'Файл не найден в архиве',
        'message' => 'Запрошенный файл :path отсутствует в скачанной копии сайта.',
        'available_pages' => 'Доступные страницы сайта:',
        'btn_home' => 'На главную страницу сайта',
        'btn_dashboard' => 'В дашборд',
    ],

    'viewer_notice' => [
        'heading' => 'Раздел не скачан:',
        'body' => 'Эта ссылка ведет в соседний раздел сайта за пределами скачанной папки:',
    ],
];
