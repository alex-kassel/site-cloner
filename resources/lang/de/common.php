<?php

declare(strict_types=1);

return [
    'title' => 'Site Cloner — Lokales Website-Archiv',
    'app_name' => 'Site Cloner',
    'app_subtitle' => 'Lokales Website-Archiv',

    'stats_sites' => 'Websites:',
    'stats_storage' => 'Speicher:',
    'stats_files' => 'Dateien:',

    'footer' => 'Site Cloner — Autonomer lokaler Website-Mirror und Template-Archiv',

    'alerts' => [
        'connecting' => 'Verbindung zum Server...',
        'audit_failed' => 'Ressourcen-Audit konnte nicht geladen werden.',
        'check_failed' => 'URL-Überprüfungsfehler: ',
        'start_failed' => 'Download konnte nicht gestartet werden.',
        'request_failed' => 'Anforderungsfehler: ',
        'delete_failed' => 'Website konnte nicht gelöscht werden.',
        'delete_error' => 'Fehler beim Löschen: ',
    ],

    'not_found' => [
        'title' => 'Datei nicht im Archiv gefunden — Site Cloner',
        'heading' => 'Datei nicht im Archiv gefunden',
        'message' => 'Die angeforderte Datei :path fehlt in der heruntergeladenen Kopie der Website.',
        'available_pages' => 'Verfügbare Seiten der Website:',
        'btn_home' => 'Zur Startseite der Website',
        'btn_dashboard' => 'Zum Dashboard',
    ],

    'viewer_notice' => [
        'heading' => 'Bereich nicht geklont:',
        'body' => 'Dieser Link führt zu einem Nachbarbereich außerhalb des geklonten Ordners:',
    ],
];
