<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentPhotoService
{
    private const DIRECTORY = 'student-photos';

    public function normalizePath(?string $path): ?string
    {
        $value = trim((string) $path);
        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            $parsedPath = parse_url($value, PHP_URL_PATH);
            $value = is_string($parsedPath) ? $parsedPath : '';
        }

        $value = str_replace('\\', '/', $value);
        $value = ltrim($value, '/');

        if (Str::startsWith($value, 'storage/')) {
            $value = ltrim(Str::after($value, 'storage/'), '/');
        }

        return $value !== '' ? $value : null;
    }

    public function storeUploadedPhoto(UploadedFile $uploadedFile): string
    {
        $storedPath = $uploadedFile->store(self::DIRECTORY, 'public');
        $normalized = $this->normalizePath($storedPath);

        if ($normalized === null || ! Storage::disk('public')->exists($normalized)) {
            throw new RuntimeException('Photo upload failed. Please try again.');
        }

        return $normalized;
    }

    public function storeCapturedPhoto(string $capturedPhoto): string
    {
        $normalized = trim($capturedPhoto);
        $pattern = '/^data:image\/(png|jpe?g|webp);base64,([a-zA-Z0-9\/+=\s]+)$/';

        if (! preg_match($pattern, $normalized, $matches)) {
            throw new RuntimeException('Invalid camera image format. Please capture the photo again.');
        }

        $extension = strtolower((string) ($matches[1] ?? 'jpg'));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $base64Payload = str_replace(' ', '+', (string) ($matches[2] ?? ''));
        $binary = base64_decode($base64Payload, true);

        if ($binary === false || $binary === '') {
            throw new RuntimeException('Unable to decode captured image. Please try again.');
        }

        if (strlen($binary) > 3 * 1024 * 1024) {
            throw new RuntimeException('Captured image is too large. Please retake with a smaller frame.');
        }

        $path = self::DIRECTORY.'/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->put($path, $binary);

        if (! Storage::disk('public')->exists($path)) {
            throw new RuntimeException('Unable to save captured image. Please try again.');
        }

        return $path;
    }

    public function resolveReadablePath(?string $path): ?string
    {
        $normalized = $this->normalizePath($path);
        if ($normalized !== null && Storage::disk('public')->exists($normalized)) {
            return $normalized;
        }

        return null;
    }

    public function photoResponse(?string $path): ?StreamedResponse
    {
        $resolved = $this->resolveReadablePath($path);
        if ($resolved === null) {
            return null;
        }

        return Storage::disk('public')->response(
            $resolved,
            null,
            [
                'Cache-Control' => 'public, max-age=86400',
                'Content-Disposition' => 'inline',
            ]
        );
    }

    public function deletePhoto(?string $path): void
    {
        $normalized = $this->normalizePath($path);
        if ($normalized === null) {
            return;
        }

        if (! Storage::disk('public')->exists($normalized)) {
            return;
        }

        Storage::disk('public')->delete($normalized);
    }
}
