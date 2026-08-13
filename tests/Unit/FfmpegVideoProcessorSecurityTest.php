<?php

use Tests\TestCase;

uses(TestCase::class);

test('ffprobe metadata reads use restrictive show entries instead of whole format and streams', function () {
    $source = file_get_contents(app_path('Services/Video/FfmpegVideoProcessor.php'));

    expect($source)->toContain("'-show_entries'")
        ->not->toContain("'-show_format'")
        ->and($source)->not->toContain("'-show_streams'");
});
