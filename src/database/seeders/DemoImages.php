<?php

namespace ME\Ecom\database\seeders;

use GdImage;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Images for the demo seeder: downloads product photos (cached on the public disk) and draws
 * banners, logos and brand marks with GD. Falls back to a drawn placeholder when offline.
 */
class DemoImages
{
    private string $font;

    private string $fontBold;

    public function __construct()
    {
        $fonts = base_path('vendor/dompdf/dompdf/lib/fonts/');
        $this->font = $fonts.'DejaVuSans.ttf';
        $this->fontBold = $fonts.'DejaVuSans-Bold.ttf';
    }

    /**
     * Download every URL to its target path (skips files already there).
     *
     * @param  array<string, string>  $targets  public-disk path => url
     * @param  callable(string): void  $fallback  draws a placeholder for a path that failed
     */
    public function download(array $targets, callable $fallback): void
    {
        $disk = Storage::disk('public');
        $missing = array_filter($targets, fn ($path) => ! $disk->exists($path), ARRAY_FILTER_USE_KEY);

        foreach (array_chunk($missing, 16, true) as $chunk) {
            try {
                $responses = Http::pool(fn (Pool $pool) => collect($chunk)
                    ->map(fn ($url, $path) => $pool->as($path)->timeout(30)->withUserAgent('Mozilla/5.0')->get($url))
                    ->all());
            } catch (Throwable) {
                $responses = [];
            }

            foreach (array_keys($chunk) as $path) {
                $response = $responses[$path] ?? null;

                if ($response instanceof Response && $response->successful() && strlen($response->body()) > 500) {
                    $disk->put($path, $response->body());
                } else {
                    $fallback($path);
                }
            }
        }
    }

    /**
     * Square product placeholder with the title written on it.
     */
    public function placeholder(string $path, string $title, array $color): void
    {
        $image = $this->gradient(800, 800, $color, $this->shade($color, -60));
        $this->textCentered($image, wordwrap($title, 18), 40, 400, [255, 255, 255]);
        $this->save($image, $path);
    }

    /**
     * Wide banner: gradient, product photos on the right, headline + button text on the left.
     *
     * @param  array<int, string>  $productPaths  public-disk paths of product photos
     * @param  array{0: int, 1: int, 2: int}  $from
     * @param  array{0: int, 1: int, 2: int}  $to
     */
    public function banner(string $path, int $width, int $height, string $eyebrow, string $title, string $subtitle, ?string $button, array $productPaths, array $from, array $to): void
    {
        $image = $this->gradient($width, $height, $from, $to);
        $scale = $height / 640;

        // Soft decorative circles
        $circle = imagecolorallocatealpha($image, 255, 255, 255, 110);
        imagefilledellipse($image, (int) ($width * 0.78), (int) ($height * 0.5), (int) ($height * 1.25), (int) ($height * 1.25), $circle);
        imagefilledellipse($image, (int) ($width * 0.05), (int) ($height * 1.05), (int) ($height * 0.7), (int) ($height * 0.7), $circle);

        // Product photos spread over the right 42% of the banner
        $photos = array_slice($productPaths, 0, 3);
        $count = max(1, count($photos));
        $areaLeft = (int) ($width * 0.56);
        $areaWidth = $width - $areaLeft - (int) (40 * $scale);
        $size = (int) min($height * 0.72, $areaWidth / (1 + ($count - 1) * 0.72));
        $step = $count > 1 ? (int) (($areaWidth - $size) / ($count - 1)) : 0;
        foreach ($photos as $i => $photo) {
            $y = (int) (($height - $size) / 2 + ($i % 2 ? $height * 0.07 : -$height * 0.05));
            $this->pastePhoto($image, $photo, $areaLeft + $i * $step, $y, $size);
        }

        $white = [255, 255, 255];
        $left = (int) (70 * $scale);
        $textWidth = $areaLeft - $left - (int) (40 * $scale);
        $this->text($image, strtoupper($eyebrow), (int) (22 * $scale), $left, (int) (170 * $scale), [255, 236, 179], true);

        $titleSize = $this->fit($title, (int) (58 * $scale), $textWidth);
        $lines = [$title];
        if ($titleSize < 44 * $scale) {
            $lines = explode("\n", wordwrap($title, 16));
            $longest = collect($lines)->sortByDesc(fn ($line) => strlen($line))->first();
            $titleSize = $this->fit($longest, (int) (58 * $scale), $textWidth);
        }
        $y = (int) (170 * $scale) + (int) ($titleSize * 1.45);
        foreach ($lines as $line) {
            $this->text($image, $line, $titleSize, $left, $y, $white, true);
            $y += (int) ($titleSize * 1.3);
        }
        $subtitleSize = $this->fit($subtitle, (int) (26 * $scale), $textWidth, false);
        $this->text($image, $subtitle, $subtitleSize, $left, $y + (int) (8 * $scale), $white);

        if ($button) {
            $buttonSize = (int) (24 * $scale);
            $box = imagettfbbox($buttonSize, 0, $this->fontBold, $button);
            $padding = (int) (34 * $scale);
            $by = $y + (int) (50 * $scale);
            $bh = (int) (64 * $scale);
            $this->roundedRect($image, $left, $by, $left + ($box[2] - $box[0]) + $padding * 2, $by + $bh, (int) (30 * $scale), [255, 255, 255]);
            $this->text($image, $button, $buttonSize, $left + $padding, $by + (int) (($bh + $buttonSize) / 2), $this->shade($from, -40), true);
        }

        $this->save($image, $path);
    }

    /**
     * Shop logo: rounded icon with a bag and the store name.
     */
    public function logo(string $path, string $name, array $color): void
    {
        $image = imagecreatetruecolor(520, 140);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

        $this->roundedRect($image, 10, 15, 120, 125, 28, $color);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagesetthickness($image, 7);
        imagerectangle($image, 38, 55, 92, 102, $white);
        imagearc($image, 65, 55, 30, 34, 180, 360, $white);
        imagesetthickness($image, 1);

        $this->text($image, $name, 46, 140, 88, [33, 37, 41], true);
        $this->text($image, 'ONLINE SHOPPING', 13, 143, 116, $color, true);
        $this->save($image, $path);
    }

    /**
     * Brand mark: initials badge + brand name.
     */
    public function brandLogo(string $path, string $name, array $color): void
    {
        $image = imagecreatetruecolor(360, 140);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        $initials = collect(preg_split('/[\s&.]+/', $name))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');

        imagefilledellipse($image, 70, 70, 110, 110, imagecolorallocate($image, ...$color));
        $this->textCentered($image, $initials, 34, 70, [255, 255, 255], 70);
        $this->text($image, mb_strlen($name) > 12 ? $name : strtoupper($name), mb_strlen($name) > 12 ? 20 : 26, 140, 82, $this->shade($color, -50), true);
        $this->save($image, $path);
    }

    /**
     * Largest font size (up to $size) at which $text fits in $maxWidth pixels.
     */
    private function fit(string $text, int $size, int $maxWidth, bool $bold = true): int
    {
        while ($size > 10) {
            $box = imagettfbbox($size, 0, $bold ? $this->fontBold : $this->font, $text);
            if ($maxWidth >= $box[2] - $box[0]) {
                break;
            }
            $size--;
        }

        return $size;
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $from
     * @param  array{0: int, 1: int, 2: int}  $to
     */
    private function gradient(int $width, int $height, array $from, array $to): GdImage
    {
        $image = imagecreatetruecolor($width, $height);

        for ($x = 0; $x < $width; $x++) {
            $t = $x / max(1, $width - 1);
            $color = imagecolorallocate($image, ...array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $t), $from, $to));
            imageline($image, $x, 0, $x, $height, $color);
        }

        return $image;
    }

    private function pastePhoto(GdImage $canvas, string $path, int $x, int $y, int $size): void
    {
        try {
            $data = Storage::disk('public')->get($path);
            $photo = $data ? @imagecreatefromstring($data) : false;
        } catch (Throwable) {
            $photo = false;
        }

        if (! $photo) {
            return;
        }

        // White card behind photos that have no transparency
        $this->roundedRect($canvas, $x - 6, $y - 6, $x + $size + 6, $y + $size + 6, 26, [255, 255, 255]);
        imagealphablending($canvas, true);
        imagecopyresampled($canvas, $photo, $x, $y, 0, 0, $size, $size, imagesx($photo), imagesy($photo));
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function text(GdImage $image, string $text, int $size, int $x, int $y, array $rgb, bool $bold = false): void
    {
        imagettftext($image, $size, 0, $x, $y, imagecolorallocate($image, ...$rgb), $bold ? $this->fontBold : $this->font, $text);
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function textCentered(GdImage $image, string $text, int $size, int $centerY, array $rgb, ?int $centerX = null): void
    {
        $lines = explode("\n", $text);
        $lineHeight = (int) ($size * 1.5);
        $y = $centerY - (int) ((count($lines) - 1) * $lineHeight / 2) + (int) ($size / 2);

        foreach ($lines as $line) {
            $box = imagettfbbox($size, 0, $this->fontBold, $line);
            $x = ($centerX ?? (int) (imagesx($image) / 2)) - (int) (($box[2] - $box[0]) / 2);
            $this->text($image, $line, $size, $x, $y, $rgb, true);
            $y += $lineHeight;
        }
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     */
    private function roundedRect(GdImage $image, int $x1, int $y1, int $x2, int $y2, int $radius, array $rgb): void
    {
        $color = imagecolorallocate($image, ...$rgb);
        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        foreach ([[$x1 + $radius, $y1 + $radius], [$x2 - $radius, $y1 + $radius], [$x1 + $radius, $y2 - $radius], [$x2 - $radius, $y2 - $radius]] as [$cx, $cy]) {
            imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $color);
        }
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $rgb
     * @return array{0: int, 1: int, 2: int}
     */
    public function shade(array $rgb, int $amount): array
    {
        return array_map(fn ($c) => max(0, min(255, $c + $amount)), $rgb);
    }

    private function save(GdImage $image, string $path): void
    {
        ob_start();
        match (true) {
            str_ends_with($path, '.png') => imagepng($image),
            str_ends_with($path, '.webp') => imagewebp($image, null, 85),
            default => imagejpeg($image, null, 88),
        };
        Storage::disk('public')->put($path, ob_get_clean());
    }
}
