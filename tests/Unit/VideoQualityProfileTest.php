<?php

use App\Services\Video\FfmpegVideoProcessor;
use Tests\TestCase;

uses(TestCase::class);

function videoProfilesFor(array $metadata, int $videoKbps): array
{
    $processor = app(FfmpegVideoProcessor::class);
    $method = new ReflectionMethod($processor, 'profilesFor');

    return $method->invoke($processor, $metadata, $videoKbps);
}

test('low bitrate long-form landscape video begins at 480p', function () {
    expect(collect(videoProfilesFor(['width' => 1920, 'height' => 1080], 600))
        ->pluck('name')
        ->all())
        ->toBe(['480']);
});

test('portrait profiles retain the equivalent 1080, 720 and 480 dimensions', function () {
    expect(videoProfilesFor(['width' => 1080, 'height' => 1920], 900))
        ->toBe([
            ['name' => '720', 'width' => 720, 'height' => 1280, 'minimum_kbps' => 850],
            ['name' => '480', 'width' => 480, 'height' => 854, 'minimum_kbps' => 250],
        ]);
});

test('video output settings reserve room below the strict 100 MB ceiling', function () {
    expect(config('video.max_output_bytes'))->toBe(100_000_000)
        ->and(config('video.target_output_bytes'))->toBeLessThan(100_000_000);
});
