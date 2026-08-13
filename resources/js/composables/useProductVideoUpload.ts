import axios from 'axios';
import { computed, ref } from 'vue';

const CHUNK_SIZE = 10 * 1024 * 1024;
const MAX_SIZE = 400_000_000;
const MAX_DURATION = 20 * 60;

type UploadState = 'idle' | 'validating' | 'initializing' | 'uploading' | 'completing' | 'completed' | 'failed';

interface VideoMetadata {
    duration: number;
    width: number;
    height: number;
}

function payload(response: { data: Record<string, unknown> }) {
    const data = response.data;

    return (data.data as Record<string, unknown> | undefined) ?? data;
}

function uploadToken(data: Record<string, unknown>): string | null {
    const upload = data.upload as Record<string, unknown> | undefined;

    return (data.token as string | undefined) ?? (data.upload_token as string | undefined) ?? (upload?.token as string | undefined) ?? null;
}

function normalizedMimeType(file: File): string {
    if (file.type) {
return file.type;
}

    const extension = file.name.split('.').pop()?.toLowerCase();

    if (extension === 'mov') {
return 'video/quicktime';
}

    if (extension === 'webm') {
return 'video/webm';
}

    return 'video/mp4';
}

function errorMessage(error: unknown): string {
    if (axios.isAxiosError(error)) {
        const data = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
        const firstError = data?.errors ? Object.values(data.errors).flat()[0] : undefined;

        return firstError ?? data?.message ?? 'The video upload could not be completed. Please try again.';
    }

    return error instanceof Error ? error.message : 'The video upload could not be completed. Please try again.';
}

function readVideoMetadata(file: File): Promise<VideoMetadata> {
    return new Promise((resolve, reject) => {
        const video = document.createElement('video');
        const objectUrl = URL.createObjectURL(file);

        video.preload = 'metadata';
        video.onloadedmetadata = () => {
            URL.revokeObjectURL(objectUrl);
            resolve({ duration: video.duration, width: video.videoWidth, height: video.videoHeight });
        };
        video.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error('We could not read this video. Please choose a valid video file.'));
        };
        video.src = objectUrl;
    });
}

export function useProductVideoUpload() {
    const state = ref<UploadState>('idle');
    const token = ref<string | null>(null);
    const progress = ref(0);
    const error = ref<string | null>(null);
    const file = ref<File | null>(null);
    const uploadedBytes = ref(0);
    const currentChunk = ref(0);
    const totalChunks = ref(0);
    let cancelled = false;

    const isBusy = computed(() => ['validating', 'initializing', 'uploading', 'completing'].includes(state.value));
    const isComplete = computed(() => state.value === 'completed');
    const requiresCompletion = computed(() => file.value !== null && !isComplete.value);

    async function validate(selectedFile: File) {
        if (selectedFile.size > MAX_SIZE) {
            throw new Error('Preview videos must be 400 MB or smaller.');
        }

        let metadata: VideoMetadata;

        try {
            metadata = await readVideoMetadata(selectedFile);
        } catch (metadataError) {
            const isQuickTime = normalizedMimeType(selectedFile) === 'video/quicktime'
                || selectedFile.name.toLowerCase().endsWith('.mov');

            if (isQuickTime) {
return;
}

            throw metadataError;
        }

        if (!Number.isFinite(metadata.duration) || metadata.duration > MAX_DURATION) {
            throw new Error('Preview videos must be 20 minutes or shorter.');
        }

        const isLandscape = metadata.width >= metadata.height;
        const exceedsDimensions = isLandscape
            ? metadata.width > 1920 || metadata.height > 1080
            : metadata.width > 1080 || metadata.height > 1920;

        if (exceedsDimensions) {
            throw new Error(isLandscape
                ? 'Landscape videos may be at most 1920 × 1080.'
                : 'Portrait videos may be at most 1080 × 1920.');
        }
    }

    async function initialise(selectedFile: File) {
        const response = await axios.post('/dashboard/seller/product-video-uploads', {
            name: selectedFile.name,
            size: selectedFile.size,
            mime_type: normalizedMimeType(selectedFile),
        });
        const newToken = uploadToken(payload(response));

        if (!newToken) {
            throw new Error('The server did not return an upload token. Please try again.');
        }

        token.value = newToken;
    }

    async function uploadChunks(selectedFile: File, startAt = 0) {
        if (!token.value) {
            throw new Error('An upload token is required before sending video chunks.');
        }

        totalChunks.value = Math.ceil(selectedFile.size / CHUNK_SIZE);

        for (let index = startAt; index < totalChunks.value; index += 1) {
            if (cancelled) {
return;
}

            currentChunk.value = index + 1;
            const start = index * CHUNK_SIZE;
            const chunk = selectedFile.slice(start, Math.min(start + CHUNK_SIZE, selectedFile.size));
            const formData = new FormData();
            formData.append('chunk', chunk, selectedFile.name);

            await axios.post(`/dashboard/seller/product-video-uploads/${token.value}/chunks/${index}`, formData, {
                onUploadProgress: (event) => {
                    const chunkProgress = event.total ? event.loaded / event.total : 0;
                    uploadedBytes.value = Math.min(selectedFile.size, start + (chunk.size * chunkProgress));
                    progress.value = Math.round((uploadedBytes.value / selectedFile.size) * 100);
                },
            });

            uploadedBytes.value = Math.min(selectedFile.size, start + chunk.size);
            progress.value = Math.round((uploadedBytes.value / selectedFile.size) * 100);
        }
    }

    async function complete() {
        if (!token.value || cancelled) {
return;
}

        state.value = 'completing';
        await axios.post(`/dashboard/seller/product-video-uploads/${token.value}/complete`);
        progress.value = 100;
        state.value = 'completed';
    }

    async function start(selectedFile: File) {
        if (token.value) {
await cancel();
}

        cancelled = false;
        file.value = selectedFile;
        error.value = null;
        progress.value = 0;
        uploadedBytes.value = 0;
        currentChunk.value = 0;
        totalChunks.value = 0;

        try {
            state.value = 'validating';
            await validate(selectedFile);

            if (cancelled) {
return;
}

            state.value = 'initializing';
            await initialise(selectedFile);

            if (cancelled) {
return;
}

            state.value = 'uploading';
            await uploadChunks(selectedFile);

            if (cancelled) {
return;
}

            await complete();
        } catch (uploadError) {
            if (!cancelled) {
                state.value = 'failed';
                error.value = errorMessage(uploadError);
            }
        }
    }

    async function retry() {
        if (!file.value) {
return;
}

        cancelled = false;
        error.value = null;

        try {
            if (!token.value) {
                await start(file.value);

                return;
            }

            const statusResponse = await axios.get(`/dashboard/seller/product-video-uploads/${token.value}`);
            const statusPayload = payload(statusResponse);

            if (statusPayload.status === 'completed') {
                progress.value = 100;
                state.value = 'completed';

                return;
            }

            const uploadedChunks = Array.isArray(statusPayload.uploaded_chunks)
                ? statusPayload.uploaded_chunks.map((value) => Number(value)).filter(Number.isInteger)
                : [];
            const firstMissingChunk = Array.from({ length: Math.ceil(file.value.size / CHUNK_SIZE) }, (_, index) => index)
                .find((index) => !uploadedChunks.includes(index)) ?? 0;
            state.value = 'uploading';
            await uploadChunks(file.value, firstMissingChunk);

            if (!cancelled) {
await complete();
}
        } catch (uploadError) {
            if (!cancelled) {
                state.value = 'failed';
                error.value = errorMessage(uploadError);
            }
        }
    }

    async function cancel() {
        cancelled = true;
        const activeToken = token.value;

        token.value = null;
        file.value = null;
        error.value = null;
        progress.value = 0;
        uploadedBytes.value = 0;
        currentChunk.value = 0;
        totalChunks.value = 0;
        state.value = 'idle';

        if (activeToken) {
            try {
                await axios.delete(`/dashboard/seller/product-video-uploads/${activeToken}`);
            } catch {
                // The user can continue editing even if a stale temporary upload cannot be cancelled.
            }
        }
    }

    return {
        state,
        token,
        progress,
        error,
        file,
        currentChunk,
        totalChunks,
        isBusy,
        isComplete,
        requiresCompletion,
        start,
        retry,
        cancel,
    };
}
