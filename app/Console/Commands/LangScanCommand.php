<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use SplFileInfo;

class LangScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lang:scan {--write= : Persist missing translations for the given domain (admin, json, or all).} {--json : Output the results as JSON}';

    /**
     * The console command description.
     */
    protected $description = 'Scan views and controllers for translation usages and report or persist missing keys.';

    /**
     * Cached translation data for quick lookups.
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    protected array $translationCache = [];

    /**
     * Tracks whether a translation file should be written back to disk.
     *
     * @var array<string, array<string, bool>>
     */
    protected array $translationDirty = [];

    protected Filesystem $files;

    protected int $bladeRewrites = 0;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): int
    {
        $writeOption = $this->option('write');
        $writeTargets = $this->parseWriteTargets($writeOption);
        if ($writeTargets === null) {
            $this->error('Invalid value for --write. Expected admin, json, or all.');

            return self::FAILURE;
        }

        $viewFiles = $this->gatherFiles(resource_path('views'), static fn (SplFileInfo $file) => Str::endsWith($file->getFilename(), '.blade.php'));
        $controllerFiles = $this->gatherFiles(app_path('Http/Controllers'), static fn (SplFileInfo $file) => Str::endsWith($file->getFilename(), '.php'));

        $entries = [];
        $manualReview = [];
        $keysCreated = 0;

        foreach ($viewFiles as $file) {
            $this->scanFile($file, 'view', $writeTargets, $entries, $manualReview, $keysCreated);
        }

        foreach ($controllerFiles as $file) {
            $this->scanFile($file, 'controller', $writeTargets, $entries, $manualReview, $keysCreated);
        }

        $this->persistTranslations();

        if ($this->option('json')) {
            $payload = [
                'entries' => array_values($entries),
                'summary' => [
                    'blades_scanned' => count($viewFiles),
                    'controllers_scanned' => count($controllerFiles),
                    'strings_wrapped' => $this->bladeRewrites,
                    'keys_created' => $keysCreated,
                    'manual_review' => array_values($manualReview),
                ],
            ];

            $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            $this->getOutput()->getErrorOutput()->writeln($this->formatSummary(
                count($viewFiles),
                count($controllerFiles),
                $this->bladeRewrites,
                $keysCreated,
                $manualReview
            ));

            return self::SUCCESS;
        }

        if (! empty($entries)) {
            $this->table(
                ['Type', 'File', 'Line', 'Key', 'Status'],
                array_map(static function (array $entry): array {
                    return [
                        ucfirst($entry['type']),
                        $entry['file'],
                        $entry['line'],
                        $entry['key'],
                        $entry['status'],
                    ];
                }, $entries)
            );
        } else {
            $this->info('No translation usages detected.');
        }

        $this->line($this->formatSummary(
            count($viewFiles),
            count($controllerFiles),
            $this->bladeRewrites,
            $keysCreated,
            $manualReview
        ));

        return self::SUCCESS;
    }

    /**
     * Parse and validate the --write option.
     *
     * @return array<string, bool>|null
     */
    protected function parseWriteTargets(?string $option): ?array
    {
        $targets = [
            'admin' => false,
            'json' => false,
        ];

        if ($option === null || $option === '') {
            return $targets;
        }

        $option = strtolower($option);
        if ($option === 'all') {
            return ['admin' => true, 'json' => true];
        }

        if (! array_key_exists($option, $targets)) {
            return null;
        }

        $targets[$option] = true;

        return $targets;
    }

    /**
     * Gather files within a directory tree using the provided filter.
     *
     * @param  callable(SplFileInfo): bool  $filter
     * @return array<int, string>
     */
    protected function gatherFiles(string $path, callable $filter): array
    {
        if (! $this->files->isDirectory($path)) {
            return [];
        }

        $files = [];
        foreach ($this->files->allFiles($path) as $file) {
            if ($filter($file)) {
                $files[] = $file->getRealPath();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Scan a file for translation usages.
     *
     * @param  array<string, bool>  $writeTargets
     * @param  array<int, array<string, mixed>>  $entries
     * @param  array<int, array<string, string>>  $manualReview
     */
    protected function scanFile(string $path, string $type, array $writeTargets, array &$entries, array &$manualReview, int &$keysCreated): void
    {
        $contents = $this->files->get($path);
        $matches = $this->findTranslationCalls($contents);

        foreach ($matches as $match) {
            $key = $match['key'];
            $default = $match['default'];
            $line = $this->calculateLineNumber($contents, $match['offset']);
            $domain = $this->determineDomain($key);

            if ($domain === null) {
                $this->addManualReview($manualReview, $key, $this->relativePath($path), 'Unable to determine translation domain.');

                $entries[] = [
                    'type' => $type,
                    'file' => $this->relativePath($path),
                    'line' => $line,
                    'key' => $key,
                    'status' => 'unknown domain',
                ];

                continue;
            }

            $status = 'ok';
            $relative = $this->relativePath($path);

            if ($this->translationExists($domain, 'en', $key)) {
                $status = 'exists';
            } else {
                $status = 'missing';
                if ($writeTargets[$domain] ?? false) {
                    $created = $this->storeTranslation($domain, $key, $default, $path);
                    if ($created) {
                        $status = 'created';
                        $keysCreated++;
                    } else {
                        $status = 'exists';
                    }
                } else {
                    $this->addManualReview($manualReview, $key, $relative, sprintf('Missing translation. Re-run with --write=%s to scaffold.', $domain));
                }
            }

            if ($status === 'created' && $default === null) {
                $this->addManualReview($manualReview, $key, $relative, 'Default text inferred automatically. Please review.');
            }

            $entries[] = [
                'type' => $type,
                'file' => $relative,
                'line' => $line,
                'key' => $key,
                'status' => $status,
            ];
        }
    }

    /**
     * Locate translation helper calls within the given contents.
     *
     * @return array<int, array{call: string, key: string, default: ?string, offset: int}>
     */
    protected function findTranslationCalls(string $contents): array
    {
        $pattern = '/(?P<call>@lang|__|trans_choice|trans|Lang::t|Lang::get|App\\\\Support\\\\Lang::t)\s*\(/';
        if (! preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $results = [];
        foreach ($matches[0] as $index => $match) {
            $start = $match[1];
            $call = $matches['call'][$index][0];
            $segment = $this->extractCallSegment($contents, $start);
            if ($segment === null) {
                continue;
            }

            $arguments = $this->splitArguments($segment['arguments']);
            if (empty($arguments)) {
                continue;
            }

            $key = $this->parseStringLiteral($arguments[0]);
            if ($key === null) {
                continue;
            }

            $default = null;
            if (isset($arguments[1])) {
                $default = $this->parseStringLiteral($arguments[1]);
            }

            $results[] = [
                'call' => $call,
                'key' => $key,
                'default' => $default,
                'offset' => $segment['offset'],
            ];
        }

        return $results;
    }

    /**
     * Extract a helper call segment, capturing the argument string and end offset.
     *
     * @return array{arguments: string, offset: int}|null
     */
    protected function extractCallSegment(string $contents, int $start): ?array
    {
        $length = strlen($contents);
        $offset = $start;
        $depth = 0;
        $arguments = '';
        $inString = false;
        $stringDelimiter = null;

        for ($i = $start; $i < $length; $i++) {
            $char = $contents[$i];

            if ($depth === 0 && $char === '(') {
                $depth = 1;
                $offset = $i + 1;
                continue;
            }

            if ($depth === 0) {
                continue;
            }

            if ($inString) {
                $arguments .= $char;
                if ($char === $stringDelimiter && ($i === 0 || $contents[$i - 1] !== '\\')) {
                    $inString = false;
                    $stringDelimiter = null;
                }
                continue;
            }

            if ($char === '\'' || $char === '"') {
                $inString = true;
                $stringDelimiter = $char;
                $arguments .= $char;
                continue;
            }

            if ($char === '(') {
                $depth++;
                $arguments .= $char;
                continue;
            }

            if ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    return [
                        'arguments' => rtrim($arguments),
                        'offset' => $offset,
                    ];
                }

                $arguments .= $char;
                continue;
            }

            $arguments .= $char;
        }

        return null;
    }

    /**
     * Split arguments string into top-level parts.
     *
     * @return array<int, string>
     */
    protected function splitArguments(string $arguments): array
    {
        $parts = [];
        $buffer = '';
        $depth = 0;
        $inString = false;
        $stringDelimiter = null;
        $length = strlen($arguments);

        for ($i = 0; $i < $length; $i++) {
            $char = $arguments[$i];

            if ($inString) {
                $buffer .= $char;
                if ($char === $stringDelimiter && ($i === 0 || $arguments[$i - 1] !== '\\')) {
                    $inString = false;
                    $stringDelimiter = null;
                }
                continue;
            }

            if ($char === '\'' || $char === '"') {
                $inString = true;
                $stringDelimiter = $char;
                $buffer .= $char;
                continue;
            }

            if ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
                $buffer .= $char;
                continue;
            }

            if ($char === ')' || $char === ']' || $char === '}') {
                $depth = max(0, $depth - 1);
                $buffer .= $char;
                continue;
            }

            if ($char === ',' && $depth === 0) {
                $parts[] = trim($buffer);
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $parts[] = trim($buffer);
        }

        return $parts;
    }

    protected function parseStringLiteral(string $expression): ?string
    {
        $expression = trim($expression);
        if ($expression === '') {
            return null;
        }

        $quote = $expression[0];
        if (($quote !== '\'' && $quote !== '"') || substr($expression, -1) !== $quote) {
            return null;
        }

        $inner = substr($expression, 1, -1);

        return stripcslashes($inner);
    }

    protected function calculateLineNumber(string $contents, int $offset): int
    {
        return substr_count(substr($contents, 0, $offset), "\n") + 1;
    }

    protected function determineDomain(string $key): ?string
    {
        if (Str::startsWith($key, 'admin.')) {
            return 'admin';
        }

        if (Str::contains($key, '.')) {
            return null;
        }

        return 'json';
    }

    protected function translationExists(string $domain, string $locale, string $key): bool
    {
        $translations = $this->loadTranslations($domain, $locale);

        if ($domain === 'admin') {
            return $this->hasNested($translations, $this->keySegments($key));
        }

        return array_key_exists($key, $translations);
    }

    protected function storeTranslation(string $domain, string $key, ?string $default, string $file): bool
    {
        $englishDefault = $default ?? $this->generateFallbackText($key);
        $arabicDefault = $default ?? $englishDefault;
        $created = false;

        if ($domain === 'admin') {
            $segments = $this->keySegments($key);
            $created = $this->storeAdminTranslation('en', $segments, $englishDefault) || $created;
            $created = $this->storeAdminTranslation('ar', $segments, $arabicDefault) || $created;
        } elseif ($domain === 'json') {
            $created = $this->storeJsonTranslation('en', $key, $englishDefault) || $created;
            $created = $this->storeJsonTranslation('ar', $key, $arabicDefault) || $created;
        }

        if ($created) {
            $this->bladeRewrites += $this->maybeRewriteBlade($domain, $key, $file);
        }

        return $created;
    }

    protected function storeAdminTranslation(string $locale, array $segments, string $value): bool
    {
        $translations = $this->loadTranslations('admin', $locale);
        if ($this->hasNested($translations, $segments)) {
            return false;
        }

        $this->setNested($translations, $segments, $value);
        $this->translationCache['admin'][$locale] = $translations;
        $this->translationDirty['admin'][$locale] = true;

        return true;
    }

    protected function storeJsonTranslation(string $locale, string $key, string $value): bool
    {
        $translations = $this->loadTranslations('json', $locale);
        if (array_key_exists($key, $translations)) {
            return false;
        }

        $translations[$key] = $value;
        ksort($translations, SORT_NATURAL | SORT_FLAG_CASE);
        $this->translationCache['json'][$locale] = $translations;
        $this->translationDirty['json'][$locale] = true;

        return true;
    }

    protected function maybeRewriteBlade(string $domain, string $key, string $file): int
    {
        if ($domain !== 'admin' || ! Str::endsWith($file, '.blade.php')) {
            return 0;
        }

        // Placeholder for future heuristics. When blade rewriting is necessary
        // ensure a .bak backup is created before modifications.
        return 0;
    }

    protected function generateFallbackText(string $key): string
    {
        $segments = explode('.', $key);
        $candidate = end($segments) ?: $key;
        $candidate = str_replace(['_', '-'], ' ', $candidate);
        $candidate = trim($candidate);

        return $candidate === '' ? $key : ucwords($candidate);
    }

    protected function keySegments(string $key): array
    {
        $segments = explode('.', $key);
        array_shift($segments); // remove domain prefix

        return $segments;
    }

    /**
     * Persist any dirty translation caches back to disk.
     */
    protected function persistTranslations(): void
    {
        foreach ($this->translationDirty as $domain => $locales) {
            foreach ($locales as $locale => $dirty) {
                if (! $dirty) {
                    continue;
                }

                $path = $this->translationPath($domain, $locale);
                $contents = $this->buildTranslationContents($domain, $locale);

                $this->writeFile($path, $contents);
            }
        }
    }

    protected function translationPath(string $domain, string $locale): string
    {
        if ($domain === 'admin') {
            return resource_path(sprintf('lang/%s/admin.php', $locale));
        }

        return resource_path(sprintf('lang/%s.json', $locale));
    }

    protected function buildTranslationContents(string $domain, string $locale): string
    {
        $data = $this->loadTranslations($domain, $locale);

        if ($domain === 'admin') {
            $this->sortRecursive($data);

            $export = var_export($data, true);
            $export = preg_replace('/^([ ]*)array \(/m', '$1[', $export);
            $export = preg_replace('/^([ ]*)\)/m', '$1]', $export);
            $export = preg_replace_callback('/^( +)/m', static function (array $matches): string {
                return str_repeat(' ', strlen($matches[0]) * 2);
            }, $export ?? '[]');

            return "<?php\n\nreturn {$export};\n";
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    protected function writeFile(string $path, string $contents): void
    {
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);
    }

    protected function loadTranslations(string $domain, string $locale): array
    {
        if (isset($this->translationCache[$domain][$locale])) {
            return $this->translationCache[$domain][$locale];
        }

        $path = $this->translationPath($domain, $locale);
        if (! $this->files->exists($path)) {
            $this->translationCache[$domain][$locale] = [];

            return [];
        }

        if ($domain === 'admin') {
            /** @var array $data */
            $data = $this->files->getRequire($path);
            $this->translationCache[$domain][$locale] = $data;

            return $data;
        }

        $contents = $this->files->get($path);
        $decoded = json_decode($contents, true) ?: [];
        $this->translationCache[$domain][$locale] = $decoded;

        return $decoded;
    }

    protected function hasNested(array $array, array $segments): bool
    {
        if (empty($segments)) {
            return false;
        }

        foreach ($segments as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }

    protected function setNested(array &$array, array $segments, string $value): void
    {
        if (empty($segments)) {
            return;
        }

        $current = &$array;
        foreach ($segments as $index => $segment) {
            if ($index === array_key_last($segments)) {
                $current[$segment] = $value;
                break;
            }

            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }

    protected function sortRecursive(array &$array): void
    {
        ksort($array, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->sortRecursive($value);
            }
        }
    }

    protected function relativePath(string $path): string
    {
        $base = base_path();
        if (Str::startsWith($path, $base)) {
            return ltrim(Str::replaceFirst($base, '', $path), DIRECTORY_SEPARATOR);
        }

        return $path;
    }

    /**
     * Append a manual review record while preventing duplicates.
     *
     * @param  array<string, array<string, string>>  $manualReview
     */
    protected function addManualReview(array &$manualReview, string $key, string $file, string $reason): void
    {
        $hash = md5($key.'|'.$file.'|'.$reason);
        $manualReview[$hash] = [
            'key' => $key,
            'file' => $file,
            'reason' => $reason,
        ];
    }

    protected function formatSummary(int $blades, int $controllers, int $wrapped, int $keysCreated, array $manualReview): string
    {
        $summary = [
            sprintf('Summary: %d blade(s) scanned, %d controller(s) scanned, %d string(s) wrapped, %d key(s) created.', $blades, $controllers, $wrapped, $keysCreated),
        ];

        if (empty($manualReview)) {
            $summary[] = 'Manual review items: none.';
        } else {
            $summary[] = 'Manual review items:';
            foreach ($manualReview as $item) {
                $summary[] = sprintf(' - %s (%s) – %s', $item['key'] ?? 'unknown', $item['file'] ?? 'n/a', $item['reason'] ?? '');
            }
        }

        return implode("\n", $summary);
    }
}
