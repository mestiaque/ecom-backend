<?php

namespace ME\Ecom\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ME\Ecom\Models\ProductImage;

/**
 * Makes a small webp copy of a product image (longest side = ecom.thumbnail.size) and saves its path
 * in ecom_product_images.thumbnail, so lists load the small file instead of the full photo.
 */
class GenerateProductThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $imageId) {}

    /**
     * Queue the job. With the "sync" queue it runs after the response is sent, so uploads stay fast.
     */
    public static function dispatchFor(ProductImage $image): void
    {
        $pending = static::dispatch($image->id);

        if (config('queue.connections.'.config('queue.default').'.driver') === 'sync') {
            $pending->afterResponse();
        }
    }

    public function handle(): void
    {
        $image = ProductImage::find($this->imageId);
        $disk = Storage::disk('public');

        if (! $image || ! $disk->exists($image->path)) {
            return;
        }

        $source = @imagecreatefromstring($disk->get($image->path));

        if (! $source) {
            return; // not an image GD can read
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, (int) config('ecom.thumbnail.size', 400) / max($width, $height));
        $thumbWidth = max(1, (int) round($width * $scale));
        $thumbHeight = max(1, (int) round($height * $scale));

        $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagefill($thumb, 0, 0, imagecolorallocatealpha($thumb, 0, 0, 0, 127));
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height);

        ob_start();
        imagewebp($thumb, null, (int) config('ecom.thumbnail.quality', 80));
        $path = dirname($image->path).'/thumbs/'.pathinfo($image->path, PATHINFO_FILENAME).'.webp';
        $disk->put($path, ob_get_clean());

        if ($image->thumbnail && $image->thumbnail !== $path) {
            $disk->delete($image->thumbnail);
        }

        $image->forceFill(['thumbnail' => $path])->saveQuietly();
    }
}
