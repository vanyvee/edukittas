<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded files after checking type, size and (for images) dimensions.
 *
 * Changes from the original fileUploadReuseable.js:
 *  - the class is closed (the usage notes were inside it, so the file did not parse);
 *  - no Intervention Image: it is not in composer.json and Laravel 13 has no Image facade.
 *    Dimensions now come from PHP's getimagesize();
 *  - the type is read from the file's real content, not just the name the browser sent;
 *  - rejected files are reported through $rejected instead of vanishing silently.
 *
 * $typeRules (unchanged):
 *   ['image' => ['extensions' => ['jpg','png'], 'maxSizeMB' => 5,
 *                'dimensions' => ['min' => [100,100], 'max' => [500,500], 'ratio' => 1, 'tolerance' => 0.08]]]
 */
class FileUploadHelper
{
    /**
     * @return array<int, array{id:string,title:string,path:string,url:string,type:string,size:float,fileType:string}>
     */
    public static function uploadFile($request, string $inputName, string $folder = 'uploads', array $typeRules = [], ?array &$rejected = null): array
    {
        $filesData = [];
        $uploaded  = $request->file($inputName);
        if (!$uploaded) return $filesData;

        $files = is_array($uploaded) ? $uploaded : [$uploaded];

        foreach ($files as $index => $file) {
            $name = $file instanceof UploadedFile ? $file->getClientOriginalName() : "file {$index}";

            if (!($file instanceof UploadedFile) || !$file->isValid()) {
                $rejected[] = "{$name}: the upload did not complete.";
                continue;
            }

            $ext    = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
            $sizeMB = $file->getSize() / 1024 / 1024;

            $matchedType = null;
            foreach ($typeRules as $type => $rules) {
                if (in_array($ext, $rules['extensions'] ?? [], true)) {
                    $matchedType = $type;
                    break;
                }
            }
            if (!$matchedType) { $rejected[] = "{$name}: file type not allowed."; continue; }

            $rules = $typeRules[$matchedType];

            if (isset($rules['maxSizeMB']) && $sizeMB > $rules['maxSizeMB']) {
                $rejected[] = "{$name}: larger than {$rules['maxSizeMB']} MB.";
                continue;
            }

            if ($matchedType === 'image' && isset($rules['dimensions']) && !self::validateImageDimensions($file, $rules['dimensions'])) {
                $rejected[] = "{$name}: image size or shape not accepted.";
                continue;
            }

            $path = $file->store($folder, 'public');
            if (!$path || !Storage::disk('public')->exists($path)) {
                $rejected[] = "{$name}: could not be saved.";
                continue;
            }

            $filesData[] = [
                'id'       => (string) Str::uuid(),
                'title'    => $request->input("{$inputName}.{$index}.title") ?? pathinfo($name, PATHINFO_FILENAME),
                'path'     => $path,
                'url'      => Storage::url($path),
                'type'     => $ext,
                'size'     => round($sizeMB, 2),
                'fileType' => $matchedType,
            ];
        }

        return $filesData;
    }

    /** Convenience: the stored path of the first accepted file, or null. */
    public static function uploadOne($request, string $inputName, string $folder, array $typeRules, ?array &$rejected = null): ?string
    {
        return self::uploadFile($request, $inputName, $folder, $typeRules, $rejected)[0]['path'] ?? null;
    }

    /**
     * @param array $rules ['ratio'=>1, 'tolerance'=>0.08, 'min'=>[100,100], 'max'=>[500,500]]
     */
    public static function validateImageDimensions(UploadedFile $file, array $rules): bool
    {
        $info = @getimagesize($file->getRealPath());
        if ($info === false || empty($info[0]) || empty($info[1])) {
            Log::warning("Image validation failed for {$file->getClientOriginalName()}: not a readable image");
            return false;
        }

        [$width, $height] = $info;
        $min       = $rules['min'] ?? [0, 0];
        $max       = $rules['max'] ?? [PHP_INT_MAX, PHP_INT_MAX];
        $ratio     = round($width / $height, 3);
        $wanted    = $rules['ratio'] ?? null;
        $tolerance = $rules['tolerance'] ?? 0.08;

        if ($width < $min[0] || $height < $min[1]) return false;
        if ($width > $max[0] || $height > $max[1]) return false;
        if ($wanted !== null && abs($ratio - $wanted) > $tolerance) return false;

        return true;
    }
}
