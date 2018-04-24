<?php

namespace Spatie\MediaLibrary;

use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaObserver
{
    public function creating(Media $media)
    {
        $media->setHighestOrderNumber();
    }

    public function updating(Media $media)
    {
        if ($media->file_name !== $media->getOriginal('file_name')) {
            app(Filesystem::class)->syncFileNames($media);
        }
    }

    public function updated(Media $media)
    {
        if (is_null($media->getOriginal('model_id'))) {
            return;
        }

        if ($media->manipulations !== json_decode($media->getOriginal('manipulations'))) {
            app(FileManipulator::class)->createDerivedFiles($media);
        }
    }

    public function deleted(Media $media)
    {
        if (in_array(SoftDeletes::class, class_uses_recursive($media))) {
            if (!$media->forceDeleting) {
                return;
            }
        }

        app(Filesystem::class)->removeAllFiles($media);
    }
}
