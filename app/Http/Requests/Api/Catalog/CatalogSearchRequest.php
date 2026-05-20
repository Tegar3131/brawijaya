<?php

namespace App\Http\Requests\Api\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:500'],
            'unit_type' => ['sometimes', 'nullable', Rule::in(['library', 'museum'])],
            'collection_type' => ['sometimes', 'nullable'],
            'collection_type.*' => ['string', 'max:80'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'category_slug' => ['sometimes', 'nullable', 'string', 'max:160'],
            'year_from' => ['sometimes', 'nullable', 'integer'],
            'year_to' => ['sometimes', 'nullable', 'integer'],
            'subject' => ['sometimes', 'nullable', 'string', 'max:255'],
            'subject_id' => ['sometimes', 'nullable', 'integer', 'exists:subjects,id'],
            'creator' => ['sometimes', 'nullable', 'string', 'max:255'],
            'creator_id' => ['sometimes', 'nullable', 'integer', 'exists:creators,id'],
            'publication_status' => [
                'sometimes',
                'nullable',
                Rule::in(['draft', 'published', 'restricted', 'archived']),
            ],
            'visibility' => [
                'sometimes',
                'nullable',
                Rule::in(['public', 'member', 'internal', 'restricted']),
            ],
            'is_featured' => ['sometimes', 'nullable', 'boolean'],
            'language_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'sort' => [
                'sometimes',
                'nullable',
                Rule::in([
                    'relevance',
                    'latest',
                    'oldest',
                    'title_asc',
                    'title_desc',
                    'year_asc',
                    'year_desc',
                    'featured',
                ]),
            ],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'include_trashed' => ['sometimes', 'nullable', 'boolean'],
        ];
    }
}