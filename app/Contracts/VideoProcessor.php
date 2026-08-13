<?php

namespace App\Contracts;

use App\Services\Video\Data\ProcessedVideo;

interface VideoProcessor
{
    /**
     * Transcode a locally staged source file into a temporary MP4 preview.
     */
    public function process(string $sourcePath, string $token): ProcessedVideo;
}
