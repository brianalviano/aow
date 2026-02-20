<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Buglinjo\LaravelWebp\Facades\Webp;

/**
 * Trait for handling both file URLs and file uploads
 * Provides unified methods for file management across models
 */
trait FileHelperTrait
{
    /**
     * Whitelist of safe file types
     * Only these file types are allowed by default
     */
    protected array $safeFileTypes = [
        // Images
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/webp' => ['webp'],
        'image/bmp' => ['bmp'],
        'image/svg+xml' => ['svg'],

        // Documents
        'application/pdf' => ['pdf'],
        'application/msword' => ['doc'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
        'application/vnd.ms-excel' => ['xls'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
        'application/vnd.ms-powerpoint' => ['ppt'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['pptx'],

        // Text files
        'text/plain' => ['txt'],
        'text/csv' => ['csv'],
        'application/json' => ['json'],
        'application/xml' => ['xml'],
        'text/xml' => ['xml'],

        // Archives (be careful with these)
        'application/zip' => ['zip'],
        'application/x-rar-compressed' => ['rar'],
        'application/x-7z-compressed' => ['7z'],

        // Audio
        'audio/mpeg' => ['mp3'],
        'audio/wav' => ['wav'],
        'audio/ogg' => ['ogg'],

        // Video
        'video/mp4' => ['mp4'],
        'video/mpeg' => ['mpeg', 'mpg'],
        'video/quicktime' => ['mov'],
        'video/x-msvideo' => ['avi'],
    ];

    /**
     * Dangerous file extensions that should never be allowed
     */
    protected array $dangerousExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps', 'pht', 'exe', 'com', 'bat', 'cmd', 'sh', 'bash', 'js', 'vbs', 'vb', 'jar', 'asp', 'aspx', 'jsp', 'jspx', 'cgi', 'pl', 'py', 'rb', 'scr', 'msi', 'app', 'deb', 'rpm', 'sql', 'sqlite', 'db'];

    /**
     * Handle file input - can be URL string or uploaded file
     *
     * @param string|UploadedFile|null $file
     * @param string|null $existingFile
     * @param string $folder
     * @return string|null
     */
    public function handleFileInput($file, ?string $existingFile = null, string $folder = 'files'): ?string
    {
        if (!$file) {
            return $existingFile;
        }

        // If it's a URL string, return as is
        if (is_string($file) && $this->isValidUrl($file)) {
            return $file;
        }

        // If it's an uploaded file, handle the upload
        if ($file instanceof UploadedFile) {
            return $this->handleFileUpload($file, $existingFile, $folder);
        }

        // If it's a string but not a URL, treat as existing file path
        if (is_string($file)) {
            return $file;
        }

        return $existingFile;
    }

    /**
     * Handle file upload and storage
     *
     * @param UploadedFile $file
     * @param string|null $existingFile
     * @param string $folder
     * @return string
     */
    protected function handleFileUpload(UploadedFile $file, ?string $existingFile = null, string $folder = 'files'): string
    {
        // Delete existing file if it exists and is not a URL
        if ($existingFile && !$this->isValidUrl($existingFile)) {
            $this->deleteFile($existingFile);
        }

        // Check if file is an image
        $isImage = $this->isImageFile($file);

        if ($isImage) {
            // For images: compress to WebP
            $filename = time() . '_' . Str::random(10) . '.webp';

            // Get full storage path
            $storagePath = storage_path('app/public/' . $folder);

            // Create directory if it doesn't exist
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            $fullPath = $storagePath . '/' . $filename;

            // Convert and compress image to WebP format
            Webp::make($file)
                ->save($fullPath);

            return '/storage/' . $folder . '/' . $filename;
        } else {
            // For non-image files: store normally
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($folder, $filename, 'public');
            return Storage::url($path);
        }
    }

    /**
     * Delete file from storage
     *
     * @param string $filePath
     * @return bool
     */
    public function deleteFile(string $filePath): bool
    {
        if ($this->isValidUrl($filePath)) {
            return true; // Don't delete external URLs
        }

        // Convert URL path to storage path
        $storagePath = str_replace('/storage/', '', $filePath);

        if (Storage::disk('public')->exists($storagePath)) {
            return Storage::disk('public')->delete($storagePath);
        }

        return true;
    }

    /**
     * Get full file URL
     *
     * @param string|null $filePath
     * @param string|null $default
     * @return string|null
     */
    public function getFileUrl(?string $filePath, ?string $default = null): ?string
    {
        if (!$filePath) {
            return $default;
        }

        // If it's already a full URL, return as is
        if ($this->isValidUrl($filePath)) {
            return $filePath;
        }

        // If it starts with /storage/, it's already a proper path
        if (str_starts_with($filePath, '/storage/')) {
            return asset($filePath);
        }

        // If it's a relative path, convert to storage URL
        return Storage::url($filePath);
    }

    /**
     * Check if string is a valid URL
     *
     * @param string $url
     * @return bool
     */
    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if uploaded file is an image
     *
     * @param UploadedFile $file
     * @return bool
     */
    protected function isImageFile(UploadedFile $file): bool
    {
        $imageMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml'];
        return in_array($file->getMimeType(), $imageMimeTypes);
    }

    /**
     * Check if file is safe (not in dangerous extensions list)
     *
     * @param UploadedFile $file
     * @return bool
     */
    protected function isSafeFile(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());

        // Check if extension is in dangerous list
        if (in_array($extension, $this->dangerousExtensions)) {
            return false;
        }

        return true;
    }

    /**
     * Validate file against whitelist
     *
     * @param UploadedFile $file
     * @return bool
     */
    protected function isWhitelistedFile(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Check if mime type exists in whitelist
        if (!isset($this->safeFileTypes[$mimeType])) {
            return false;
        }

        // Check if extension matches the mime type
        $allowedExtensions = $this->safeFileTypes[$mimeType];
        if (!in_array($extension, $allowedExtensions)) {
            return false;
        }

        return true;
    }

    /**
     * Get allowed file types for validation
     *
     * @return array
     */
    public function getAllowedMimeTypes(): array
    {
        return array_keys($this->safeFileTypes);
    }

    /**
     * Get allowed extensions for validation
     *
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        $extensions = [];
        foreach ($this->safeFileTypes as $exts) {
            $extensions = array_merge($extensions, $exts);
        }
        return array_unique($extensions);
    }

    /**
     * Validate file
     *
     * @param UploadedFile $file
     * @param array $options - ['max_size' => 10MB, 'allowed_types' => [], 'max_dimensions' => [2048, 2048], 'skip_whitelist' => false]
     * @return array
     */
    public function validateFile(UploadedFile $file, array $options = []): array
    {
        $errors = [];

        // SECURITY: Always check for dangerous files first
        if (!$this->isSafeFile($file)) {
            $errors[] = 'File type is not allowed for security reasons';
            return $errors; // Return immediately for dangerous files
        }

        // SECURITY: Check whitelist by default (unless explicitly skipped)
        $skipWhitelist = $options['skip_whitelist'] ?? false;
        if (!$skipWhitelist && !$this->isWhitelistedFile($file)) {
            $errors[] = 'File type is not in the allowed list. Allowed: ' . implode(', ', $this->getAllowedExtensions());
            return $errors;
        }

        // Default max size: 10MB
        $maxSize = $options['max_size'] ?? 10 * 1024 * 1024;

        // Check file size
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);
            $errors[] = "File size must be less than {$maxSizeMB}MB";
        }

        // Check file type if specified (additional custom validation)
        if (isset($options['allowed_types']) && !empty($options['allowed_types'])) {
            if (!in_array($file->getMimeType(), $options['allowed_types'])) {
                $errors[] = 'File type not allowed';
            }
        }

        // Check image dimensions only if file is an image
        if ($this->isImageFile($file)) {
            $imageInfo = getimagesize($file->getPathname());
            if ($imageInfo && isset($options['max_dimensions'])) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                $maxWidth = $options['max_dimensions'][0] ?? 2048;
                $maxHeight = $options['max_dimensions'][1] ?? 2048;

                if ($width > $maxWidth || $height > $maxHeight) {
                    $errors[] = "Image dimensions must be less than {$maxWidth}x{$maxHeight} pixels";
                }
            }
        }

        return $errors;
    }

    /**
     * Get validation rules for file input
     *
     * @param bool $required
     * @param array $options - Validation options to pass to validateFile
     * @return array
     */
    public function getFileValidationRules(bool $required = false, array $options = []): array
    {
        $rules = [];

        if ($required) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Allow either URL string or file upload
        $rules[] = function ($attribute, $value, $fail) use ($options) {
            if (is_string($value)) {
                // If it's a string, it should be a valid URL
                if (!$this->isValidUrl($value)) {
                    // Unless it's an existing file path
                    if (!str_starts_with($value, '/storage/') && !Storage::disk('public')->exists($value)) {
                        $fail('The ' . $attribute . ' must be a valid URL or uploaded file.');
                    }
                }
            } elseif ($value instanceof UploadedFile) {
                // Validate uploaded file
                $errors = $this->validateFile($value, $options);
                if (!empty($errors)) {
                    $fail('The ' . $attribute . ' ' . implode(', ', $errors));
                }
            } else {
                $fail('The ' . $attribute . ' must be a valid URL or uploaded file.');
            }
        };

        return $rules;
    }
}
