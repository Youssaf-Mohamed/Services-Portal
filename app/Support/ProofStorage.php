<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProofStorage
{
    /**
     * Store a proof file for a specific user.
     * 
     * IMPORTANT: Proofs are stored privately and should NOT be exposed via public URLs.
     * Access to proofs will be restricted to authorized personnel via protected endpoints.
     *
     * @param UploadedFile $file The uploaded proof image
     * @param int $userId The ID of the user this proof belongs to
     * @return string The internal storage path (e.g., "user_123/abc-def-ghi.jpg")
     */
    public static function storeProof(UploadedFile $file, int $userId): string
    {
        $disk = Storage::disk(config('transport.proof_disk', 'proofs'));

        // Generate UUID filename while keeping original extension
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;

        // Store under user-specific directory
        $directory = "user_{$userId}";
        $path = $directory . '/' . $filename;

        // Store the file
        $disk->putFileAs($directory, $file, $filename);

        return $path;
    }

    /**
     * Check if a proof file exists.
     *
     * @param string $path The internal storage path
     * @return bool
     */
    public static function exists(string $path): bool
    {
        return Storage::disk(config('transport.proof_disk', 'proofs'))->exists($path);
    }

    /**
     * Delete a proof file.
     *
     * @param string $path The internal storage path
     * @return bool
     */
    public static function delete(string $path): bool
    {
        return Storage::disk(config('transport.proof_disk', 'proofs'))->delete($path);
    }

    /**
     * Get the full filesystem path to a proof (for serving via protected endpoint).
     * Note: This should only be used by protected download endpoints.
     *
     * @param string $path The internal storage path
     * @return string The full filesystem path
     */
    public static function getPath(string $path): string
    {
        return Storage::disk(config('transport.proof_disk', 'proofs'))->path($path);
    }
}
