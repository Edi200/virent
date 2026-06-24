<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('fleet:migrate-media-to-public', function () {
    $migrated = 0;

    Media::query()
        ->where('disk', 'local')
        ->each(function (Media $media) use (&$migrated): void {
            $path = $media->getPathRelativeToRoot();

            if (! Storage::disk('local')->exists($path)) {
                $this->warn("Missing file for media {$media->id}: {$path}");

                return;
            }

            Storage::disk('public')->writeStream(
                $path,
                Storage::disk('local')->readStream($path),
            );

            Storage::disk('local')->delete($path);

            $media->update([
                'disk' => 'public',
                'conversions_disk' => 'public',
            ]);

            $migrated++;
        });

    $this->info("Migrated {$migrated} media file(s) to the public disk.");
})->purpose('Move fleet media from the private local disk to the public disk');
