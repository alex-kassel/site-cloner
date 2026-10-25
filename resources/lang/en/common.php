<?php

declare(strict_types=1);

return [
    'title' => 'Site Cloner — Local Website Archive',
    'app_name' => 'Site Cloner',
    'app_subtitle' => 'Local Website Archive',

    'stats_sites' => 'Websites:',
    'stats_storage' => 'Storage:',
    'stats_files' => 'Files:',

    'footer' => 'Site Cloner — Autonomous local website mirror and template archive',

    'alerts' => [
        'connecting' => 'Connecting to server...',
        'audit_failed' => 'Failed to load resources audit.',
        'check_failed' => 'URL verification error: ',
        'start_failed' => 'Could not start download.',
        'request_failed' => 'Request error: ',
        'delete_failed' => 'Failed to delete website.',
        'delete_error' => 'Delete error: ',
    ],

    'not_found' => [
        'title' => 'File not found in archive — Site Cloner',
        'heading' => 'File not found in archive',
        'message' => 'The requested file :path is missing from the downloaded website copy.',
        'available_pages' => 'Available website pages:',
        'btn_home' => 'To website home',
        'btn_dashboard' => 'To dashboard',
    ],

    'viewer_notice' => [
        'heading' => 'Section not downloaded:',
        'body' => 'This link points to an uncloned section outside the downloaded folder scope:',
    ],
];
