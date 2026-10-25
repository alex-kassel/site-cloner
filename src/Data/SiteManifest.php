<?php

declare(strict_types=1);

namespace AlexKassel\SiteCloner\Data;

final class SiteManifest
{
    /**
     * @param  array<int, string>  $pages
     * @param  array<int, array{time: string, message: string, level: string}>  $logs
     * @param  array<string, mixed>  $externalResources
     */
    public function __construct(
        public string $id,
        public string $url,
        public string $baseUrl,
        public string $scopePath,
        public string $status = 'pending',
        public int $filesCount = 0,
        public int $totalBytes = 0,
        public int $pagesCount = 0,
        public ?string $currentFile = null,
        public ?string $errorMessage = null,
        public ?string $startedAt = null,
        public ?string $completedAt = null,
        public int $elapsedSeconds = 0,
        public array $pages = [],
        public array $logs = [],
        public array $externalResources = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            baseUrl: (string) ($data['baseUrl'] ?? ($data['base_url'] ?? '')),
            scopePath: (string) ($data['scopePath'] ?? ($data['scope_path'] ?? '/')),
            status: (string) ($data['status'] ?? 'pending'),
            filesCount: (int) ($data['filesCount'] ?? ($data['files_count'] ?? 0)),
            totalBytes: (int) ($data['totalBytes'] ?? ($data['total_bytes'] ?? 0)),
            pagesCount: (int) ($data['pagesCount'] ?? ($data['pages_count'] ?? 0)),
            currentFile: isset($data['currentFile']) ? (string) $data['currentFile'] : (isset($data['current_file']) ? (string) $data['current_file'] : null),
            errorMessage: isset($data['errorMessage']) ? (string) $data['errorMessage'] : (isset($data['error_message']) ? (string) $data['error_message'] : null),
            startedAt: isset($data['startedAt']) ? (string) $data['startedAt'] : (isset($data['started_at']) ? (string) $data['started_at'] : null),
            completedAt: isset($data['completedAt']) ? (string) $data['completedAt'] : (isset($data['completed_at']) ? (string) $data['completed_at'] : null),
            elapsedSeconds: (int) ($data['elapsedSeconds'] ?? ($data['elapsed_seconds'] ?? 0)),
            pages: is_array($data['pages'] ?? null) ? $data['pages'] : [],
            logs: is_array($data['logs'] ?? null) ? $data['logs'] : [],
            externalResources: is_array($data['externalResources'] ?? null) ? $data['externalResources'] : (is_array($data['external_resources'] ?? null) ? $data['external_resources'] : []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'base_url' => $this->baseUrl,
            'scope_path' => $this->scopePath,
            'status' => $this->status,
            'files_count' => $this->filesCount,
            'total_bytes' => $this->totalBytes,
            'pages_count' => $this->pagesCount,
            'current_file' => $this->currentFile,
            'error_message' => $this->errorMessage,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'elapsed_seconds' => $this->elapsedSeconds,
            'pages' => $this->pages,
            'logs' => $this->logs,
            'external_resources' => $this->externalResources,
            'human_size' => $this->humanReadableSize(),
        ];
    }

    public function humanReadableSize(): string
    {
        $bytes = $this->totalBytes;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return sprintf('%.2f %s', $bytes / pow(1024, $i), $units[$i]);
    }
}
