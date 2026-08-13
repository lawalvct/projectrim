# aaPanel video processing

Install a current FFmpeg build with `ffmpeg` and `ffprobe` available at fixed, absolute paths (normally `/usr/bin/ffmpeg` and `/usr/bin/ffprobe`). Set those paths in the site's `.env`; never use a shell command string for either binary.

Set the PHP CLI `memory_limit` high enough for FFmpeg orchestration (the media processing itself runs out of process), then run a dedicated worker under Supervisor:

```ini
[program:projectrim-video]
command=/www/server/php/83/bin/php /www/wwwroot/projectrim/artisan queue:work video_database --queue=videos --tries=2 --timeout=7200 --sleep=3
directory=/www/wwwroot/projectrim
autostart=true
autorestart=true
stopwaitsecs=7800
user=www
numprocs=1
redirect_stderr=true
stdout_logfile=/www/wwwroot/projectrim/storage/logs/video-worker.log
```

Also run Laravel's scheduler once per minute so `videos:prune` removes expired staged files and abandoned working directories:

```cron
* * * * * www cd /www/wwwroot/projectrim && /www/server/php/83/bin/php artisan schedule:run >> /dev/null 2>&1
```

The `video_database` connection must have `retry_after=7500`, which is longer than the 7200-second worker timeout. Grant the web/worker user read/write access to `storage/app/private`, `storage/app/public`, and `storage/app/video-processing`; ensure the public storage symlink exists. Keep the source limit at 400 MB and reserve enough temporary disk space for two-pass encodes.
The default capacity guard reserves 5 GB of free disk and limits active staged uploads to 8 GB across all sellers. Adjust `VIDEO_MINIMUM_FREE_BYTES` and `VIDEO_MAX_GLOBAL_ACTIVE_UPLOAD_BYTES` only after checking the VPS disk and expected queue volume.
The short assembly step is serialized by default so multiple 400 MB uploads cannot simultaneously create full temporary copies. Keep the scheduler active; it removes expired stale assemblies while preserving an assembly that is still fresh.

## Web and PHP limits

Uploads are resumable 10 MiB chunks, so the web server does not need to accept a single 400 MB request. In aaPanel, set Nginx `client_max_body_size` and PHP `upload_max_filesize` to at least `16M`, and set `post_max_size` to at least `20M`. Apply the same limits to any reverse proxy or CDN. Verify the CLI PHP used by Supervisor has `pcntl` enabled and does not disable `proc_open`, `proc_get_status`, or `proc_terminate`.

After every deployment run migrations, refresh cached configuration, and restart workers:

```bash
/www/server/php/83/bin/php artisan migrate --force
/www/server/php/83/bin/php artisan optimize
/www/server/php/83/bin/php artisan queue:restart
```

Before enabling uploads, verify the binaries and codecs:

```bash
/usr/bin/ffmpeg -version
/usr/bin/ffprobe -version
/usr/bin/ffmpeg -hide_banner -encoders | grep libx264
```
