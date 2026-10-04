<?php

namespace ME\Ecom\Console;

use Illuminate\Console\Command;
use ME\Ecom\Jobs\GenerateProductThumbnail;
use ME\Ecom\Models\ProductImage;

class GenerateThumbnails extends Command
{
    protected $signature = 'ecom:thumbnails {--force : Remake thumbnails that already exist}';

    protected $description = 'Create thumbnails for product images that do not have one yet';

    public function handle(): int
    {
        $images = ProductImage::query()->when(! $this->option('force'), fn ($q) => $q->whereNull('thumbnail'));
        $bar = $this->output->createProgressBar($images->count());

        $images->lazyById()->each(function (ProductImage $image) use ($bar) {
            GenerateProductThumbnail::dispatchSync($image->id);
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();
        $this->info(ProductImage::whereNull('thumbnail')->count().' images still without a thumbnail (missing or unreadable files).');

        return self::SUCCESS;
    }
}
