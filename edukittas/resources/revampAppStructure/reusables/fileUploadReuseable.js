<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class FileUploadHelper
{
    /**
     * Upload and validate one or more files.
     */
    public static function uploadFile($request, string $inputName, string $folder = 'uploads', array $typeRules = [])
{
    $filesData = [];
    $uploaded = $request->file($inputName);
    if (!$uploaded) return $filesData;

    $files = is_array($uploaded) ? $uploaded : [$uploaded];

    foreach ($files as $index => $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) continue;

        $ext = strtolower($file->getClientOriginalExtension());
        $sizeMB = $file->getSize() / 1024 / 1024;

        // Detect type based on extension
        $matchedType = null;
        foreach ($typeRules as $type => $rules) {
            if (in_array($ext, $rules['extensions'] ?? [])) {
                $matchedType = $type;
                break;
            }
        }

        if (!$matchedType) continue; // skip unknown extensions

        $rules = $typeRules[$matchedType];

        // ✅ Check max size
        if (isset($rules['maxSizeMB']) && $sizeMB > $rules['maxSizeMB']) continue;

        // ✅ Optional dimension check for images
        if ($matchedType === 'image' && isset($rules['dimensions'])) {
            if (!self::validateImageDimensions($file, $rules['dimensions'])) continue;
        }

        // Store file
        $path = $file->store($folder, 'public');
        if (!Storage::disk('public')->exists($path)) continue;

        // Build file data
        $filesData[] = [
            'id' => (string) Str::uuid(),
            'title' => $request->input("{$inputName}.{$index}.title") ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'path' => $path,
            'url' => Storage::url($path),
            'type' => $ext,
            'size' => round($sizeMB,2),
            'fileType' => $matchedType
        ];
    }

    return $filesData;
}
    /**
     * ✅ Validate an image’s dimensions and aspect ratio.
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @param  array  $rules  ['ratio'=>1, 'min'=>[100,100], 'max'=>[500,500]]
     * @return bool
     */
    public static function validateImageDimensions(UploadedFile $file, array $rules): bool
{
    try {
        // Use Intervention Image to analyze
        $image = Image::make($file->getRealPath());
        $width = $image->width();
        $height = $image->height();
        $ratio = round($width / $height, 3);

        // Defaults
        $min = $rules['min'] ?? [0, 0];
        $max = $rules['max'] ?? [INF, INF];
        $requiredRatio = $rules['ratio'] ?? null;
        $tolerance = $rules['tolerance'] ?? 0.08; // 8% leeway (was 5%)

        // ✅ Validate min dimensions
        if ($width < $min[0] || $height < $min[1]) {
            return false;
        }

        // ✅ Validate max dimensions
        if ($width > $max[0] || $height > $max[1]) {
            return false;
        }

        // ✅ Ratio check (e.g. must be roughly 1:1)
        if ($requiredRatio !== null && abs($ratio - $requiredRatio) > $tolerance) {
            return false;
        }

        return true;

    } catch (\Exception $e) {
        // In case the file isn’t an image or is corrupt
        \Log::warning("Image validation failed for {$file->getClientOriginalName()}: {$e->getMessage()}");
        return false;
    }
}


usage.
use App\Helpers\FileUploadHelper;

$typeRules = [
    'image' => [
        'extensions' => ['jpg','jpeg','png'],
        'maxSizeMB' => 5,
        'dimensions' => ['ratio'=>1, 'min'=>[100,100], 'max'=>[500,500]]
    ],
    'video' => [
        'extensions' => ['mp4','mov','webm'],
        'maxSizeMB' => 50
    ],
    
    'document' => [ 
        'extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'],
        'maxSizeMB' => 10
    ],
    
    'audio' => [
        'extensions' => ['mp3','wav'],
        'maxSizeMB' => 10
    ]
];

$filesData = FileUploadHelper::uploadMultipleWithTypes($request, 'files', 'uploads', $typeRules);