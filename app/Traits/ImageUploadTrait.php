<?php
namespace App\Traits;

trait ImageUploadTrait
{
    public function saveLogo($base64, $folder = 'logo')
    {
        if (!$base64) return null;

        $meta = null;
        if (str_starts_with($base64, 'data:image')) {
            [$meta, $base64] = explode(',', $base64);
        }

        $binaryData = base64_decode($base64);
        $extension = 'png';
        
        if ($meta) {
            if (str_contains($meta, 'jpeg')) $extension = 'jpg';
            if (str_contains($meta, 'svg')) $extension = 'svg';
        }

        $fileName = uniqid() . '.' . $extension;
        $relativePath = "public/$folder";
        $fullPath = base_path($relativePath);

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        file_put_contents("$fullPath/$fileName", $binaryData);

        return "$relativePath/$fileName";
    }

    public function deleteImageIfExists($path)
    {
        if ($path && file_exists(base_path($path))) {
            unlink(base_path($path));
        }
    }
}