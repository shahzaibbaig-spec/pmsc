<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;
use ZipArchive;

class TeacherEResourceService
{
    private const RESOURCE_ROOT = 'e-resources';

    private const FILE_INDEX_CACHE_KEY = 'teacher_e_resources.file_index';

    private const FILE_INDEX_CACHE_TTL_SECONDS = 1800;

    /**
     * @var array<int, string>
     */
    private const ALLOWED_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'csv', 'txt', 'rtf',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg',
    ];

    /**
     * @var array<int, string>
     */
    private const PRINTABLE_EXTENSIONS = [
        'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'txt',
    ];

    /**
     * @param array<int, string> $zipPaths
     * @return array{
     *   zip_files_processed:int,
     *   nested_zip_files_processed:int,
     *   resource_files_extracted:int,
     *   skipped_files:int,
     *   errors:array<int, string>
     * }
     */
    public function importFromZipPaths(array $zipPaths, bool $fresh = false): array
    {
        $resourceRoot = $this->resourceRootPath();

        if ($fresh && File::isDirectory($resourceRoot)) {
            File::deleteDirectory($resourceRoot);
        }

        File::ensureDirectoryExists($resourceRoot);
        File::ensureDirectoryExists($this->tempRootPath());

        $summary = [
            'zip_files_processed' => 0,
            'nested_zip_files_processed' => 0,
            'resource_files_extracted' => 0,
            'skipped_files' => 0,
            'errors' => [],
        ];

        foreach ($zipPaths as $index => $zipPath) {
            $zipPath = trim($zipPath);
            if ($zipPath === '') {
                continue;
            }

            if (! File::exists($zipPath)) {
                $summary['errors'][] = 'Zip file not found: '.$zipPath;
                continue;
            }

            $this->extractZipRecursively($zipPath, $resourceRoot, $summary, $index === 0);
        }

        try {
            Cache::forget(self::FILE_INDEX_CACHE_KEY);
        } catch (Throwable) {
            // Ignore cache backend failures during import (for example, unavailable DB cache store).
        }

        return $summary;
    }

    /**
     * @return array{
     *   teacher:\App\Models\Teacher|null,
     *   sessions:array<int, string>,
     *   selected_session:string,
     *   class_resources:array<int, array{
     *      class_id:int,
     *      class_name:string,
     *      resources:array<int, array{
     *          label:string,
     *          extension:string,
     *          size:string,
     *          token:string,
     *          printable:bool,
     *          download_url:string,
     *          print_url:string,
     *          search_haystack:string
     *      }>
     *   }>,
     *   general_resources:array<int, array{
     *      label:string,
     *      extension:string,
     *      size:string,
     *      token:string,
     *      printable:bool,
     *      download_url:string,
     *      print_url:string,
     *      search_haystack:string
     *   }>
     * }
     */
    public function buildTeacherResourcesPayload(int $userId, ?string $requestedSession = null): array
    {
        $teacher = Teacher::query()
            ->with('user:id,name,email')
            ->where('user_id', $userId)
            ->first();

        $defaultSession = $this->sessionFromDate(now()->toDateString());
        $sessions = $teacher instanceof Teacher
            ? $this->availableSessionsForTeacher((int) $teacher->id, $defaultSession)
            : [$defaultSession];

        $selectedSession = trim((string) $requestedSession);
        if ($selectedSession === '') {
            $selectedSession = $sessions[0] ?? $defaultSession;
        }
        if (! in_array($selectedSession, $sessions, true)) {
            array_unshift($sessions, $selectedSession);
            $sessions = array_values(array_unique($sessions));
        }

        if (! $teacher instanceof Teacher) {
            return [
                'teacher' => null,
                'sessions' => $sessions,
                'selected_session' => $selectedSession,
                'class_resources' => [],
                'general_resources' => [],
            ];
        }

        $assignedClassIds = TeacherAssignment::query()
            ->where('teacher_id', (int) $teacher->id)
            ->where('session', $selectedSession)
            ->pluck('class_id')
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $assignedClasses = SchoolClass::query()
            ->whereIn('id', $assignedClassIds->all())
            ->orderBy('name')
            ->orderBy('section')
            ->get(['id', 'name', 'section']);

        $fileIndex = $this->listResourceFiles();
        $classResources = [];
        $generalResources = [];

        foreach ($assignedClasses as $class) {
            $classResources[(int) $class->id] = [
                'class_id' => (int) $class->id,
                'class_name' => trim((string) $class->name.' '.(string) ($class->section ?? '')),
                'resources' => [],
            ];
        }

        foreach ($fileIndex as $file) {
            $matchedClassIds = $this->matchClassIdsForResource((string) ($file['relative_path'] ?? ''), $assignedClasses);
            $resource = $this->mapResourceForSession($file, $selectedSession);

            if (empty($matchedClassIds)) {
                $generalResources[] = $resource;
                continue;
            }

            foreach ($matchedClassIds as $classId) {
                if (! isset($classResources[$classId])) {
                    continue;
                }

                $classResources[$classId]['resources'][] = $resource;
            }
        }

        foreach ($classResources as $classId => $group) {
            $classResources[$classId]['resources'] = $this->sortResources($group['resources']);
        }

        return [
            'teacher' => $teacher,
            'sessions' => $sessions,
            'selected_session' => $selectedSession,
            'class_resources' => array_values($classResources),
            'general_resources' => $this->sortResources($generalResources),
        ];
    }

    public function resolveAbsolutePathForToken(string $token): ?string
    {
        $relativePath = $this->decodeTokenToRelativePath($token);
        if ($relativePath === null) {
            return null;
        }

        $root = $this->resourceRootPath();
        $candidate = $root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (! File::exists($candidate) || ! File::isFile($candidate)) {
            return null;
        }

        $realRoot = realpath($root);
        $realCandidate = realpath($candidate);
        if (! is_string($realRoot) || ! is_string($realCandidate)) {
            return null;
        }

        $realRootLower = strtolower($realRoot);
        $realCandidateLower = strtolower($realCandidate);
        $prefix = rtrim($realRootLower, '\\/').DIRECTORY_SEPARATOR;
        if (! str_starts_with($realCandidateLower, strtolower($prefix)) && $realCandidateLower !== $realRootLower) {
            return null;
        }

        return $realCandidate;
    }

    public function isPrintableExtension(string $extension): bool
    {
        return in_array(strtolower($extension), self::PRINTABLE_EXTENSIONS, true);
    }

    private function resourceRootPath(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.self::RESOURCE_ROOT);
    }

    private function tempRootPath(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.self::RESOURCE_ROOT.DIRECTORY_SEPARATOR.'_tmp');
    }

    /**
     * @param array<string, int|array<int, string>> $summary
     */
    private function extractZipRecursively(string $zipPath, string $resourceRoot, array &$summary, bool $topLevel): void
    {
        $archive = new ZipArchive();
        $openResult = $archive->open($zipPath);
        if ($openResult !== true) {
            $summary['errors'][] = 'Unable to open zip: '.$zipPath;
            return;
        }

        if ($topLevel) {
            $summary['zip_files_processed']++;
        } else {
            $summary['nested_zip_files_processed']++;
        }

        try {
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $entryName = (string) $archive->getNameIndex($index);
                if ($entryName === '' || str_ends_with($entryName, '/')) {
                    continue;
                }

                $extension = strtolower((string) pathinfo($entryName, PATHINFO_EXTENSION));
                $entryStream = $archive->getStream($entryName);
                if (! is_resource($entryStream)) {
                    $summary['errors'][] = 'Unable to read zip entry: '.$entryName.' in '.$zipPath;
                    continue;
                }

                if ($extension === 'zip') {
                    $tempZipFile = $this->tempRootPath().DIRECTORY_SEPARATOR.uniqid('nested_', true).'.zip';
                    File::ensureDirectoryExists(dirname($tempZipFile));
                    $tempHandle = fopen($tempZipFile, 'wb');
                    if (! is_resource($tempHandle)) {
                        fclose($entryStream);
                        $summary['errors'][] = 'Unable to create temp nested zip file for '.$entryName;
                        continue;
                    }

                    stream_copy_to_stream($entryStream, $tempHandle);
                    fclose($tempHandle);
                    fclose($entryStream);

                    $this->extractZipRecursively($tempZipFile, $resourceRoot, $summary, false);
                    @unlink($tempZipFile);
                    continue;
                }

                if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    fclose($entryStream);
                    $summary['skipped_files']++;
                    continue;
                }

                $sanitizedRelative = $this->sanitizeRelativePath($entryName);
                if ($sanitizedRelative === '') {
                    fclose($entryStream);
                    $summary['skipped_files']++;
                    continue;
                }

                $targetPath = $resourceRoot.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $sanitizedRelative);
                $targetPath = $this->normalizeWindowsPathLength($resourceRoot, $targetPath);
                $targetPath = $this->uniqueFilePath($targetPath);

                File::ensureDirectoryExists(dirname($targetPath));
                $targetHandle = fopen($targetPath, 'wb');
                if (! is_resource($targetHandle)) {
                    fclose($entryStream);
                    $summary['errors'][] = 'Unable to create target file: '.$targetPath;
                    continue;
                }

                stream_copy_to_stream($entryStream, $targetHandle);
                fclose($targetHandle);
                fclose($entryStream);

                $summary['resource_files_extracted']++;
            }
        } finally {
            $archive->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private function availableSessionsForTeacher(int $teacherId, string $defaultSession): array
    {
        $sessions = TeacherAssignment::query()
            ->where('teacher_id', $teacherId)
            ->select('session')
            ->distinct()
            ->orderByDesc('session')
            ->pluck('session')
            ->filter(static fn ($value): bool => is_string($value) && trim($value) !== '')
            ->values()
            ->all();

        if (! in_array($defaultSession, $sessions, true)) {
            array_unshift($sessions, $defaultSession);
        }

        return array_values(array_unique($sessions));
    }

    private function sessionFromDate(string $date): string
    {
        $dateTime = Carbon::parse($date);
        $startYear = $dateTime->month >= 7 ? $dateTime->year : ($dateTime->year - 1);

        return $startYear.'-'.($startYear + 1);
    }

    /**
     * @return array<int, array{
     *   relative_path:string,
     *   extension:string,
     *   size_bytes:int
     * }>
     */
    private function listResourceFiles(): array
    {
        try {
            return Cache::remember(self::FILE_INDEX_CACHE_KEY, self::FILE_INDEX_CACHE_TTL_SECONDS, function (): array {
                return $this->scanResourceFiles();
            });
        } catch (Throwable) {
            return $this->scanResourceFiles();
        }
    }

    /**
     * @return array<int, array{
     *   relative_path:string,
     *   extension:string,
     *   size_bytes:int
     * }>
     */
    private function scanResourceFiles(): array
    {
        $root = $this->resourceRootPath();
        if (! File::isDirectory($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if (! $item instanceof \SplFileInfo || ! $item->isFile()) {
                continue;
            }

            $absolutePath = $item->getPathname();
            $relative = str_replace('\\', '/', ltrim(str_replace($root, '', $absolutePath), '\\/'));

            if (str_starts_with($relative, '_tmp/')) {
                continue;
            }

            $extension = strtolower((string) $item->getExtension());
            if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $files[] = [
                'relative_path' => $relative,
                'extension' => $extension,
                'size_bytes' => (int) $item->getSize(),
            ];
        }

        usort($files, static fn (array $a, array $b): int => strcmp(
            (string) ($a['relative_path'] ?? ''),
            (string) ($b['relative_path'] ?? '')
        ));

        return $files;
    }

    /**
     * @param Collection<int, SchoolClass> $classes
     * @return array<int, int>
     */
    private function matchClassIdsForResource(string $relativePath, Collection $classes): array
    {
        $haystack = $this->normalizeForSearch($relativePath);
        $matched = [];

        foreach ($classes as $class) {
            $classId = (int) $class->id;
            $className = trim((string) $class->name.' '.(string) ($class->section ?? ''));
            $classNeedle = $this->normalizeForSearch($className);
            if ($classNeedle !== '' && str_contains($haystack, $classNeedle)) {
                $matched[] = $classId;
                continue;
            }

            $numericMatch = $this->extractNumericGrade($className);
            if ($numericMatch !== null) {
                $pattern = '/\b(?:grade|class|g|kg)\s*0?'.preg_quote((string) $numericMatch, '/').'\b/i';
                if (preg_match($pattern, $haystack) === 1) {
                    $matched[] = $classId;
                    continue;
                }
            }

            if ($this->containsEarlyYearsAlias($className, $haystack)) {
                $matched[] = $classId;
            }
        }

        return array_values(array_unique($matched));
    }

    private function containsEarlyYearsAlias(string $className, string $haystack): bool
    {
        $normalizedClass = $this->normalizeForSearch($className);

        if (str_contains($normalizedClass, 'nursery') && str_contains($haystack, 'nursery')) {
            return true;
        }
        if (str_contains($normalizedClass, 'prep') && str_contains($haystack, 'prep')) {
            return true;
        }
        if (
            (str_contains($normalizedClass, 'kg') || str_contains($normalizedClass, 'kindergarten'))
            && (str_contains($haystack, 'kg') || str_contains($haystack, 'kindergarten'))
        ) {
            return true;
        }
        if (
            (str_contains($normalizedClass, 'play group') || str_contains($normalizedClass, 'playgroup') || str_contains($normalizedClass, 'preschool'))
            && (str_contains($haystack, 'play group') || str_contains($haystack, 'playgroup') || str_contains($haystack, 'preschool'))
        ) {
            return true;
        }

        return false;
    }

    private function extractNumericGrade(string $className): ?int
    {
        if (preg_match('/\b(\d{1,2})\b/', $className, $matches) !== 1) {
            return null;
        }

        $value = (int) ($matches[1] ?? 0);

        return $value > 0 ? $value : null;
    }

    /**
     * @param array<string, mixed> $file
     * @return array{
     *   label:string,
     *   extension:string,
     *   size:string,
     *   token:string,
     *   printable:bool,
     *   download_url:string,
     *   print_url:string,
     *   search_haystack:string
     * }
     */
    private function mapResourceForSession(array $file, string $session): array
    {
        $relativePath = (string) ($file['relative_path'] ?? '');
        $extension = strtolower((string) ($file['extension'] ?? pathinfo($relativePath, PATHINFO_EXTENSION)));
        $sizeBytes = (int) ($file['size_bytes'] ?? 0);
        $token = $this->encodeRelativePathToToken($relativePath);
        $label = $this->resourceLabelFromPath($relativePath);
        $size = $this->humanSize($sizeBytes);

        return [
            'label' => $label,
            'extension' => strtoupper($extension),
            'size' => $size,
            'token' => $token,
            'printable' => $this->isPrintableExtension($extension),
            'download_url' => route('teacher.e-resources.file', [
                'token' => $token,
                'session' => $session,
                'mode' => 'download',
            ]),
            'print_url' => route('teacher.e-resources.file', [
                'token' => $token,
                'session' => $session,
                'mode' => 'inline',
            ]),
            'search_haystack' => $this->normalizeForSearch($label.' '.$extension.' '.$relativePath),
        ];
    }

    /**
     * @param array<int, array{
     *   label:string,
     *   extension:string,
     *   size:string,
     *   token:string,
     *   printable:bool,
     *   download_url:string,
     *   print_url:string,
     *   search_haystack:string
     * }> $resources
     * @return array<int, array{
     *   label:string,
     *   extension:string,
     *   size:string,
     *   token:string,
     *   printable:bool,
     *   download_url:string,
     *   print_url:string,
     *   search_haystack:string
     * }>
     */
    private function sortResources(array $resources): array
    {
        usort($resources, static fn (array $a, array $b): int => strcmp(
            (string) ($a['label'] ?? ''),
            (string) ($b['label'] ?? '')
        ));

        return $resources;
    }

    private function resourceLabelFromPath(string $relativePath): string
    {
        $fileName = pathinfo($relativePath, PATHINFO_FILENAME);
        $fileName = str_replace(['_', '-'], ' ', $fileName);
        $fileName = preg_replace('/\s+/', ' ', $fileName) ?? $fileName;
        $fileName = trim($fileName);
        $label = $fileName !== '' ? ucwords($fileName) : 'Resource File';

        $directory = trim((string) dirname(str_replace('\\', '/', $relativePath)));
        if ($directory === '' || $directory === '.' || $directory === '/') {
            return $label;
        }

        $segments = array_values(array_filter(explode('/', $directory), static fn ($segment): bool => trim($segment) !== ''));
        if ($segments === []) {
            return $label;
        }

        $parent = str_replace(['_', '-'], ' ', (string) end($segments));
        $parent = preg_replace('/\s+/', ' ', $parent) ?? $parent;
        $parent = trim($parent);
        if ($parent === '') {
            return $label;
        }

        return $label.' ('.ucwords($parent).')';
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / (1024 * 1024), 2).' MB';
    }

    private function normalizeForSearch(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['\\', '/', '_', '-'], ' ', $value);
        $value = preg_replace('/[^a-z0-9\s]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }

    private function sanitizeRelativePath(string $relativePath): string
    {
        $segments = array_filter(explode('/', str_replace('\\', '/', $relativePath)), static fn ($segment): bool => $segment !== '');
        $cleanSegments = [];

        foreach ($segments as $segment) {
            $segment = trim((string) $segment);
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }

            $segment = preg_replace('/[<>:"|?*]+/', ' ', $segment) ?? $segment;
            $segment = preg_replace('/\s+/', ' ', $segment) ?? $segment;
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            if (mb_strlen($segment) > 80) {
                $extension = pathinfo($segment, PATHINFO_EXTENSION);
                $base = pathinfo($segment, PATHINFO_FILENAME);
                $base = mb_substr((string) $base, 0, 60);
                $segment = $extension !== ''
                    ? $base.'.'.mb_substr((string) $extension, 0, 12)
                    : $base;
            }

            $cleanSegments[] = $segment;
        }

        return implode('/', $cleanSegments);
    }

    private function normalizeWindowsPathLength(string $resourceRoot, string $targetPath): string
    {
        if (strlen($targetPath) <= 230) {
            return $targetPath;
        }

        $extension = pathinfo($targetPath, PATHINFO_EXTENSION);
        $filename = pathinfo($targetPath, PATHINFO_FILENAME);
        $filename = preg_replace('/\s+/', '_', trim((string) $filename)) ?? 'resource_file';
        $filename = substr($filename, 0, 40);
        $hash = substr(sha1($targetPath), 0, 16);

        $shortRelative = '_long'.DIRECTORY_SEPARATOR.$filename.'_'.$hash.($extension !== '' ? '.'.$extension : '');

        return rtrim($resourceRoot, '\\/').DIRECTORY_SEPARATOR.$shortRelative;
    }

    private function uniqueFilePath(string $targetPath): string
    {
        if (! File::exists($targetPath)) {
            return $targetPath;
        }

        $dir = dirname($targetPath);
        $name = pathinfo($targetPath, PATHINFO_FILENAME);
        $ext = pathinfo($targetPath, PATHINFO_EXTENSION);
        $counter = 1;

        do {
            $candidate = $dir.DIRECTORY_SEPARATOR.$name.'_'.$counter.($ext !== '' ? '.'.$ext : '');
            $counter++;
        } while (File::exists($candidate));

        return $candidate;
    }

    private function encodeRelativePathToToken(string $relativePath): string
    {
        return rtrim(strtr(base64_encode($relativePath), '+/', '-_'), '=');
    }

    private function decodeTokenToRelativePath(string $token): ?string
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $normalized = strtr($token, '-_', '+/');
        $padLength = strlen($normalized) % 4;
        if ($padLength > 0) {
            $normalized .= str_repeat('=', 4 - $padLength);
        }

        $decoded = base64_decode($normalized, true);
        if (! is_string($decoded) || trim($decoded) === '') {
            return null;
        }

        $relativePath = str_replace('\\', '/', $decoded);
        $relativePath = ltrim($relativePath, '/');

        if (str_contains($relativePath, '../') || str_starts_with($relativePath, '..')) {
            return null;
        }

        return $relativePath;
    }
}
