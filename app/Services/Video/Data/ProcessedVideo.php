<?php

namespace App\Services\Video\Data;

final readonly class ProcessedVideo
{
    public function __construct(
        public string $temporaryPath,
        public int $size,
        public int $durationMs,
        public int $width,
        public int $height,
        public string $videoCodec,
        public ?string $audioCodec,
    ) {}
}
