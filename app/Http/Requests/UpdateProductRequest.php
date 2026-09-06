<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    private const MAX_UPLOAD_KILOBYTES = 51200;

    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product && $product->user_id === $this->user()->id;
    }

    /**
     * The edit form always submits both video fields, and `prohibits` counts any
     * non-empty value as present — including the "0" that FormData sends for a
     * false boolean. Null out the inactive field so only a genuine conflict
     * (a new upload *and* a removal in the same request) is rejected.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        if (! $this->boolean('remove_video')) {
            $normalized['remove_video'] = null;
        }

        if (blank($this->input('preview_video_upload_token'))) {
            $normalized['preview_video_upload_token'] = null;
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    public function rules(): array
    {
        $product = $this->route('product');
        $hasExistingProjectFile = $product?->files()->exists() ?? false;

        return [
            'title' => ['required', 'string', 'max:255'],
            'faculty_id' => ['nullable', 'exists:faculties,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'abstract' => ['nullable', 'string'],
            'table_of_content' => ['nullable', 'string'],
            'chapter_one' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'document_type' => ['nullable', 'string', 'max:100'],
            'class_of_degree' => ['nullable', 'string', 'max:100'],
            'institution' => ['nullable', 'string', 'max:255'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'location_region' => ['nullable', 'string', 'max:100'],
            'date_available' => ['nullable', 'date'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'project_file' => [$hasExistingProjectFile ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip', 'max:'.self::MAX_UPLOAD_KILOBYTES],
            'preview_video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:'.self::MAX_UPLOAD_KILOBYTES, 'prohibits:preview_video_upload_token,remove_video'],
            'preview_video_upload_token' => ['nullable', 'uuid', 'prohibits:preview_video,remove_video', Rule::exists('product_video_uploads', 'token')->where(fn ($query) => $query->where('user_id', $this->user()->id)->where('status', 'completed')->where('expires_at', '>', now()))],
            'remove_video' => ['nullable', 'boolean', 'prohibits:preview_video,preview_video_upload_token'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', 'exists:product_images,id'],
            'co_authors' => ['nullable', 'array', 'max:10'],
            'co_authors.*.user_id' => ['required', 'exists:users,id'],
            'co_authors.*.contribution_percentage' => ['required', 'numeric', 'min:1', 'max:99'],
            'status' => ['nullable', 'in:draft,pending'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_file.required' => 'The project file field is required.',
            'project_file.max' => 'The project file must not be greater than 50 MB.',
            'preview_video.max' => 'The preview video must not be greater than 50 MB.',
            'preview_video.prohibits' => 'You cannot upload a new preview video and remove the current one in the same save.',
            'preview_video_upload_token.prohibits' => 'You cannot upload a new preview video and remove the current one in the same save.',
            'remove_video.prohibits' => 'You cannot remove the current preview video and upload a new one in the same save.',
        ];
    }
}
