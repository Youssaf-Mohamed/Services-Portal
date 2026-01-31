<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IdCardStorage
{
    /**
     * The disk used for ID card files (same as transport proofs).
     */
    protected static function disk(): string
    {
        return 'proofs';
    }

    /**
     * Store a transfer screenshot.
     *
     * @param UploadedFile $file The uploaded screenshot
     * @param int $userId The ID of the user
     * @return string The internal storage path
     */
    public static function storeScreenshot(UploadedFile $file, int $userId): string
    {
        return self::storeFile($file, $userId, 'id-card/screenshots');
    }

    /**
     * Store a new photo for photo change requests.
     *
     * @param UploadedFile $file The uploaded photo
     * @param int $userId The ID of the user
     * @return string The internal storage path
     */
    public static function storeNewPhoto(UploadedFile $file, int $userId): string
    {
        return self::storeFile($file, $userId, 'id-card/photos');
    }

    /**
     * Internal helper to store a file.
     */
    protected static function storeFile(UploadedFile $file, int $userId, string $subdirectory): string
    {
        $disk = Storage::disk(self::disk());

        // Generate UUID filename while keeping original extension
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;

        // Store under user-specific directory
        $directory = "{$subdirectory}/user_{$userId}";
        $path = $directory . '/' . $filename;

        // Store the file
        $disk->putFileAs($directory, $file, $filename);

        return $path;
    }

    /**
     * Check if a file exists.
     *
     * @param string|null $path The internal storage path
     * @return bool
     */
    public static function exists(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }
        return Storage::disk(self::disk())->exists($path);
    }

    /**
     * Delete a file.
     *
     * @param string|null $path The internal storage path
     * @return bool
     */
    public static function delete(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }
        return Storage::disk(self::disk())->delete($path);
    }

    /**
     * Get the full filesystem path.
     *
     * @param string $path The internal storage path
     * @return string The full filesystem path
     */
    public static function getPath(string $path): string
    {
        return Storage::disk(self::disk())->path($path);
    }

    /**
     * Get the MIME type of a file.
     *
     * @param string $path The internal storage path
     * @return string|null
     */
    public static function mimeType(string $path): ?string
    {
        if (!self::exists($path)) {
            return null;
        }
        return Storage::disk(self::disk())->mimeType($path);
    }

    /**
     * Read the file stream for downloading.
     *
     * @param string $path The internal storage path
     * @return resource|null
     */
    public static function readStream(string $path)
    {
        if (!self::exists($path)) {
            return null;
        }
        return Storage::disk(self::disk())->readStream($path);
    }
}
