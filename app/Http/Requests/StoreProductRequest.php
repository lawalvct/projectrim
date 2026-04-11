<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSeller() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
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
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5000'],
            'project_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,zip', 'max:1001200'],
            'co_authors' => ['nullable', 'array', 'max:10'],
            'co_authors.*.user_id' => ['required', 'exists:users,id'],
            'co_authors.*.contribution_percentage' => ['required', 'numeric', 'min:1', 'max:99'],
            'status' => ['nullable', 'in:draft,pending'],
            'notify_users' => ['nullable', 'boolean'],
        ];
    }
}
