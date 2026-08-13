<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductVideoUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductVideoUploadController extends Controller
{
    private const MAX_SOURCE_BYTES = 400_000_000;

    private const DEFAULT_CHUNK_SIZE = 10485760;

    private const MIME_TYPES = ['video/mp4', 'video/webm', 'video/quicktime'];

    public function initialize(Request $request): JsonResponse
    {
        $values = $request->validate(['name' => ['required', 'string', 'max:255'], 'size' => ['required', 'integer', 'min:1', 'max:'.$this->maxSourceBytes()], 'mime_type' => ['required', 'string', Rule::in(self::MIME_TYPES)]]);
        $chunkSize = $this->chunkSize();
        $capacityLock = Cache::lock('product-video-upload-capacity', 10);
        if (! $capacityLock->get()) {
            throw ValidationException::withMessages(['upload' => 'Video upload capacity is busy. Please try again.']);
        }

        try {
            $upload = DB::transaction(function () use ($request, $values, $chunkSize) {
                DB::table('users')->where('id', $request->user()->id)->lockForUpdate()->first();
                $active = $this->activeUploads()->where('user_id', $request->user()->id);
                $activeCount = (clone $active)->count();
                $activeBytes = (int) (clone $active)->sum('expected_size');
                $globalActiveBytes = (int) $this->activeUploads()->sum('expected_size');
                $incomingBytes = (int) $values['size'];

                if ($activeCount >= (int) config('video.max_active_uploads', 3)
                    || $activeBytes + $incomingBytes > (int) config('video.max_active_upload_bytes', 800_000_000)
                    || $globalActiveBytes + $incomingBytes > (int) config('video.max_global_active_upload_bytes', 8_000_000_000)) {
                    throw ValidationException::withMessages([
                        'upload' => 'Preview-video upload capacity is full. Finish or cancel an existing upload before starting another.',
                    ]);
                }

                $this->assertStorageCapacity($incomingBytes);

                return ProductVideoUpload::create([
                    'user_id' => $request->user()->id,
                    'token' => (string) Str::uuid(),
                    'original_name' => $values['name'],
                    'mime_type' => $values['mime_type'],
                    'expected_size' => $incomingBytes,
                    'total_chunks' => (int) ceil($incomingBytes / $chunkSize),
                    'uploaded_chunks' => [],
                    'expires_at' => now()->addHours(max(1, (int) config('video.upload_ttl_hours', 24))),
                ]);
            });
        } finally {
            $capacityLock->release();
        }

        return response()->json($this->payload($upload, ['chunk_size' => $chunkSize]), 201);
    }

    public function uploadChunk(Request $request, string $token, int $index): JsonResponse
    {
        $values = $request->validate(['chunk' => ['required', 'file', 'max:'.(int) ceil($this->chunkSize() / 1024)], 'checksum' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/i']]);
        $upload = DB::transaction(function () use ($request, $token, $index, $values) {
            $upload = $this->findUpload($request, $token, true);
            $this->assertReceiving($upload);
            if ($index < 0 || $index >= $upload->total_chunks) {
                throw ValidationException::withMessages(['index' => 'Invalid chunk index.']);
            }
            $chunk = $values['chunk'];
            $size = $this->chunkBytes($upload, $index);
            $disk = Storage::disk($this->sourceDisk());
            $path = $this->chunkPath($upload, $index);
            if ($chunk->getSize() !== $size) {
                throw ValidationException::withMessages(['chunk' => "Expected {$size} bytes."]);
            }
            if ($disk->exists($path)) {
                $existingIsValid = $disk->size($path) === $size
                    && (! isset($values['checksum']) || hash_file('sha256', $disk->path($path)) === strtolower($values['checksum']));

                if ($existingIsValid) {
                    $this->recordChunk($upload, $index);

                    return $upload;
                }

                $disk->delete($path);
            }
            if (isset($values['checksum']) && hash_file('sha256', $chunk->getRealPath()) !== strtolower($values['checksum'])) {
                throw ValidationException::withMessages(['checksum' => 'Chunk checksum mismatch.']);
            }
            $disk->makeDirectory($this->chunkDirectory($upload));
            $stream = fopen($chunk->getRealPath(), 'rb');
            try {
                if (! $disk->writeStream($path, $stream)) {
                    throw new \RuntimeException('Unable to store the preview-video chunk.');
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            $this->recordChunk($upload, $index);

            return $upload;
        });

        return response()->json($this->payload($upload, ['chunk_uploaded' => true]));
    }

    public function complete(Request $request, string $token): JsonResponse
    {
        $upload = DB::transaction(function () use ($request, $token) {
            $upload = $this->findUpload($request, $token, true);
            $this->expire($upload);
            if ($upload->status === 'completed') {
                return $upload;
            }
            if ($upload->status === 'assembling') {
                $staleBefore = now()->subSeconds(max(1, (int) config('video.assembly_stale_seconds', 900)));

                if (! $upload->updated_at || $upload->updated_at->greaterThan($staleBefore)) {
                    throw ValidationException::withMessages([
                        'upload' => 'This preview video is already being finalized. Please wait a moment and try again.',
                    ]);
                }

                $upload->forceFill(['status' => 'uploading'])->save();
            }
            $this->assertReceiving($upload);
            $indexes = range(0, $upload->total_chunks - 1);
            if (collect($upload->uploaded_chunks ?? [])->map(fn ($value) => (int) $value)->sort()->values()->all() !== $indexes || $upload->received_size !== $upload->expected_size) {
                throw ValidationException::withMessages(['upload' => 'All chunks are required.']);
            }
            $disk = Storage::disk($this->sourceDisk());
            foreach ($indexes as $index) {
                if (! $disk->exists($this->chunkPath($upload, $index)) || $disk->size($this->chunkPath($upload, $index)) !== $this->chunkBytes($upload, $index)) {
                    throw ValidationException::withMessages(['upload' => 'Invalid upload chunk.']);
                }
            }
            $upload->forceFill(['status' => 'assembling'])->save();

            return $upload;
        });

        if ($upload->status === 'completed') {
            return response()->json($this->payload($upload));
        }

        $assemblyLock = Cache::lock('product-video-assembly', max(30, (int) config('video.assembly_lock_seconds', 900)));
        if (! $assemblyLock->get()) {
            ProductVideoUpload::query()
                ->where('id', $upload->id)
                ->where('status', 'assembling')
                ->update(['status' => 'uploading']);
            throw ValidationException::withMessages([
                'upload' => 'Another preview video is being finalized. Please try again in a moment.',
            ]);
        }

        $disk = Storage::disk($this->sourceDisk());
        $indexes = range(0, $upload->total_chunks - 1);
        $source = $this->sourcePath($upload);
        $temporarySource = $source.'.assembling';

        try {
            $this->assertStorageCapacity((int) $upload->expected_size, 1);
            $disk->makeDirectory(dirname($source));
            $output = fopen($disk->path($temporarySource), 'wb');
            if ($output === false) {
                throw new \RuntimeException('Unable to create the assembled preview video.');
            }
            try {
                foreach ($indexes as $index) {
                    $input = $disk->readStream($this->chunkPath($upload, $index));
                    if ($input === false) {
                        throw new \RuntimeException('Unable to read an uploaded video chunk.');
                    }
                    try {
                        stream_copy_to_stream($input, $output);
                    } finally {
                        fclose($input);
                    }
                }
            } finally {
                fclose($output);
            }
            if (! $disk->exists($temporarySource) || $disk->size($temporarySource) !== $upload->expected_size) {
                throw ValidationException::withMessages(['upload' => 'Invalid assembled size.']);
            }
            $disk->delete($source);
            if (! $disk->move($temporarySource, $source)) {
                throw new \RuntimeException('Unable to finalize the assembled preview video.');
            }

            $upload = DB::transaction(function () use ($request, $token, $source) {
                $locked = $this->findUpload($request, $token, true);
                if ($locked->status !== 'assembling') {
                    throw ValidationException::withMessages(['upload' => 'This upload can no longer be completed.']);
                }
                $locked->forceFill(['status' => 'completed', 'source_path' => $source, 'completed_at' => now()])->save();

                return $locked;
            });
            $disk->deleteDirectory($this->chunkDirectory($upload));
        } catch (\Throwable $exception) {
            $disk->delete($temporarySource);
            ProductVideoUpload::query()
                ->where('id', $upload->id)
                ->where('status', 'assembling')
                ->update(['status' => 'uploading']);
            throw $exception;
        } finally {
            $assemblyLock->release();
        }

        return response()->json($this->payload($upload));
    }

    public function show(Request $request, string $token): JsonResponse
    {
        $upload = $this->findUpload($request, $token);
        $this->expire($upload);

        return response()->json($this->payload($upload));
    }

    public function destroy(Request $request, string $token): JsonResponse
    {
        $upload = DB::transaction(function () use ($request, $token) {
            $upload = $this->findUpload($request, $token, true);
            if ($upload->claimed_at || in_array($upload->status, ['claimed', 'assembling'], true)) {
                throw ValidationException::withMessages(['upload' => 'A claimed upload cannot be cancelled.']);
            }
            $upload->delete();

            return $upload;
        });
        Storage::disk($this->sourceDisk())->deleteDirectory($this->directory($upload));

        return response()->json(status: 204);
    }

    private function findUpload(Request $request, string $token, bool $lock = false): ProductVideoUpload
    {
        $query = ProductVideoUpload::where('token', $token)->where('user_id', $request->user()->id);

        return ($lock ? $query->lockForUpdate() : $query)->firstOrFail();
    }

    private function assertReceiving(ProductVideoUpload $upload): void
    {
        $this->expire($upload);
        if ($upload->status !== 'uploading') {
            throw ValidationException::withMessages(['upload' => 'This upload is no longer accepting chunks.']);
        }
    }

    private function activeUploads()
    {
        return ProductVideoUpload::query()->where(function ($query): void {
            $query->where(function ($expiring): void {
                $expiring->whereIn('status', ['uploading', 'completed', 'assembling'])
                    ->where('expires_at', '>', now());
            })->orWhere('status', 'claimed');
        });
    }

    private function assertStorageCapacity(int $incomingBytes, int $workingCopies = 2): void
    {
        $freeBytes = @disk_free_space(Storage::disk($this->sourceDisk())->path(''));
        $requiredBytes = (float) config('video.minimum_free_bytes', 5_000_000_000)
            + ((float) $incomingBytes * $workingCopies)
            + (float) config('video.max_output_bytes', 100_000_000);

        if ($freeBytes === false || (float) $freeBytes < $requiredBytes) {
            throw ValidationException::withMessages([
                'upload' => 'The server does not currently have enough safe storage capacity for this video.',
            ]);
        }
    }

    private function expire(ProductVideoUpload $upload): void
    {
        if ($upload->status === 'uploading' && $upload->isExpired()) {
            $upload->forceFill(['status' => 'expired'])->save();
        }
    }

    private function payload(ProductVideoUpload $upload, array $extra = []): array
    {
        return array_merge(['token' => $upload->token, 'status' => $upload->status, 'expected_size' => $upload->expected_size, 'received_size' => $upload->received_size, 'total_chunks' => $upload->total_chunks, 'uploaded_chunks' => $upload->uploaded_chunks ?? [], 'expires_at' => $upload->expires_at->toIso8601String(), 'completed_at' => $upload->completed_at?->toIso8601String()], $extra);
    }

    private function chunkSize(): int
    {
        return max(1, (int) config('video.chunk_size', self::DEFAULT_CHUNK_SIZE));
    }

    private function maxSourceBytes(): int
    {
        return max(1, (int) config('video.max_source_size', self::MAX_SOURCE_BYTES));
    }

    private function chunkBytes(ProductVideoUpload $upload, int $index): int
    {
        return $index === $upload->total_chunks - 1 ? $upload->expected_size - ($this->chunkSize() * $index) : $this->chunkSize();
    }

    private function recordChunk(ProductVideoUpload $upload, int $index): void
    {
        $chunks = collect($upload->uploaded_chunks ?? [])
            ->map(fn ($value) => (int) $value)
            ->push($index)
            ->filter(fn (int $value) => $value >= 0 && $value < $upload->total_chunks)
            ->unique()
            ->sort()
            ->values();
        $receivedSize = $chunks->sum(fn (int $value) => $this->chunkBytes($upload, $value));

        $upload->forceFill([
            'received_size' => $receivedSize,
            'uploaded_chunks' => $chunks->all(),
        ])->save();
    }

    private function directory(ProductVideoUpload $upload): string
    {
        return trim((string) config('video.upload_directory', 'product-video-uploads'), '/').'/'.$upload->token;
    }

    private function chunkDirectory(ProductVideoUpload $upload): string
    {
        return $this->directory($upload).'/chunks';
    }

    private function chunkPath(ProductVideoUpload $upload, int $index): string
    {
        return $this->chunkDirectory($upload).'/'.$index.'.part';
    }

    private function sourcePath(ProductVideoUpload $upload): string
    {
        return $this->directory($upload).'/source.'.match ($upload->mime_type) {
            'video/webm' => 'webm', 'video/quicktime' => 'mov', default => 'mp4'
        };
    }

    private function sourceDisk(): string
    {
        return (string) config('video.source_disk', 'local');
    }
}
