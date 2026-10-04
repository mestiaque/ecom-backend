<?php

namespace ME\Ecom\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ME\Http\Controllers\Controller;

/**
 * Base for every ecom admin controller.
 */
abstract class EcomController extends Controller
{
    protected function perPage(): int
    {
        return (int) get_setting('pagination', 15) ?: 15;
    }

    /**
     * Store an uploaded image on the public disk and return its path.
     */
    protected function storeImage(UploadedFile $file, string $folder): string
    {
        return $file->store(config('ecom.upload_dir').'/'.$folder, 'public');
    }

    /**
     * Replace the image in $field when a new file was uploaded (or remove it when "remove_{$field}" is checked).
     */
    protected function replaceImage(Request $request, string $field, ?string $current, string $folder): ?string
    {
        if ($request->hasFile($field)) {
            $this->deleteImage($current);

            return $this->storeImage($request->file($field), $folder);
        }

        if ($request->boolean("remove_{$field}")) {
            $this->deleteImage($current);

            return null;
        }

        return $current;
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * ?from= / ?to= date range, defaulting to the last $days days.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function dateRange(Request $request, int $days = 30): array
    {
        $from = $request->date('from') ? CarbonImmutable::parse($request->date('from'))->startOfDay() : CarbonImmutable::now()->subDays($days - 1)->startOfDay();
        $to = $request->date('to') ? CarbonImmutable::parse($request->date('to'))->endOfDay() : CarbonImmutable::now()->endOfDay();

        return $from > $to ? [$to->startOfDay(), $from->endOfDay()] : [$from, $to];
    }
}
