<?php declare(strict_types=1);

namespace BayAreaWebPro\NovaFieldCkEditor;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class MediaStorage
{
    /**
     * Storage Disk
     */
    private string $disk;

    /**
     * MediaStorage constructor.
     * @param string $disk
     */
    public function __construct($disk = 'media')
    {
        $this->disk = $disk;
    }

    /**
     * Make Instance
     * @param string $disk
     * @return static
     */
    public static function make($disk = 'media'): self
    {
        return app('ckeditor-media-storage', compact('disk'));
    }

    /**
     * Save a new media file from the Nova request.
     * @param Request $request
     * @throws Throwable
     * @return array
     */
    public function __invoke(Request $request)
    {
        return $this->handleUpload($request->file('file'));
    }

    /**
     * Handle the File Upload
     * @param UploadedFile $file
     * @throws Throwable
     * @return array
     */
    public function handleUpload(UploadedFile $file): array
    {

        $attributes = $this->resize($file);

        $file->storePubliclyAs('', $attributes['file'], [
            'disk' => $this->disk,
        ]);

        return array_merge($attributes, [
            'disk' => $this->disk,
        ]);
    }

    /**
     * Perform Resize & Conversion Operations.
     * @param UploadedFile $file
     * @throws Throwable
     * @return array
     */
    protected function resize(UploadedFile $file): array
    {
        ini_set('memory_limit', config('nova-ckeditor.memory', '256M'));

        $maxWidth = config('nova-ckeditor.max_width', 1024);
        $maxHeight = config('nova-ckeditor.max_height', 768);

        // Hash Original Data.
        $hash = md5_file($file->getRealPath());

        // Make new filename.
        $name = sprintf(
            "%s.{$file->guessExtension()}",
            Str::slug(Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 120, ''))
        );

        // Resize the image.
        $image = Image::make($file->getRealPath());
        if ($image->width() > $maxWidth || $image->height() > $maxHeight) {
            $image->resize($maxWidth, $maxHeight, function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $image->save($file->getRealPath(), config('nova-ckeditor.max_quality', 75));

        return [
            'hash' => $hash,
            'file' => $name,
            'mime'   => $image->mime(),
            'width'  => $image->width(),
            'height' => $image->height(),
            'size'   => $this->optimize($file->getRealPath()),
        ];
    }

    /**
     * Perform Optimization Operations.
     * @param string $tempPath
     * @throws Throwable
     * @return int
     */
    public function optimize(string $tempPath):int
    {
        ImageOptimizer::optimize($tempPath);
        return filesize($tempPath);
    }

    /**
     * Get formatted bytes.
     * @param int $bytes
     * @return string
     */
    public static function bytesForHumans(int $bytes): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the URL for the media file.
     * @param string $file
     * @return mixed
     */
    public function url(string $file)
    {
        return Storage::disk($this->disk)->url($file);
    }
}
