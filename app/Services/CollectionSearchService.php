<?php

namespace App\Services;

use App\Models\Collection as CollectionModel;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CollectionSearchService
{
    /**
     * Search katalog koleksi.
     *
     * Contoh $filters:
     * [
     *   'q' => 'brawijaya',
     *   'unit_type' => 'library',
     *   'collection_type' => 'book',
     *   'category_id' => 1,
     *   'category_slug' => 'buku',
     *   'year_from' => 1945,
     *   'year_to' => 2024,
     *   'subject' => 'Metadata',
     *   'creator' => 'Nugroho',
     *   'visibility' => 'public',
     *   'publication_status' => 'published',
     *   'is_featured' => true,
     *   'sort' => 'relevance',
     *   'per_page' => 10,
     * ]
     *
     * @throws ValidationException
     */
    public function search(array $filters = [], ?User $user = null, ?string $ipAddress = null): LengthAwarePaginator
    {
        $filters = $this->validateFilters($filters);

        $query = CollectionModel::query()
            ->with([
                'category',
                'currentLocation',
                'creators',
                'subjects',
                'primaryAsset',
                'libraryItem',
                'museumItem',
            ]);

        $this->applyDefaultVisibility($query, $filters, $user);
        $this->applyKeyword($query, $filters);
        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 10;

        $result = $query->paginate($perPage)->withQueryString();

        $this->logSearch($filters, $user, $ipAddress, $result->total());

        return $result;
    }

    /**
     * Search publik. Default hanya menampilkan koleksi published + public.
     */
    public function publicSearch(array $filters = [], ?User $user = null, ?string $ipAddress = null): LengthAwarePaginator
    {
        $filters['publication_status'] = $filters['publication_status'] ?? 'published';
        $filters['visibility'] = $filters['visibility'] ?? 'public';

        return $this->search($filters, $user, $ipAddress);
    }

    private function applyDefaultVisibility(Builder $query, array $filters, ?User $user): void
    {
        if (! empty($filters['include_trashed'])) {
            $query->withTrashed();
        }

        if (! array_key_exists('publication_status', $filters)) {
            $query->where('publication_status', 'published');
        }

        if (! array_key_exists('visibility', $filters)) {
            if ($user?->hasAnyRole(['admin', 'pustakawan', 'kurator'])) {
                return;
            }

            if ($user && $user->hasRole('member')) {
                $query->whereIn('visibility', ['public', 'member']);
                return;
            }

            $query->where('visibility', 'public');
        }
    }

    private function applyKeyword(Builder $query, array $filters): void
    {
        $keyword = trim((string) ($filters['q'] ?? ''));

        if ($keyword === '') {
            return;
        }

        $booleanQuery = $this->toBooleanFullTextQuery($keyword);
        $likeQuery = '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';

        $query->addSelect('collections.*');

        $query->selectRaw(
            "MATCH(record_code, title, subtitle, description) AGAINST (? IN BOOLEAN MODE) AS relevance_score",
            [$booleanQuery]
        );

        $query->where(function (Builder $where) use ($booleanQuery, $likeQuery) {
            $where->whereRaw(
                "MATCH(record_code, title, subtitle, description) AGAINST (? IN BOOLEAN MODE)",
                [$booleanQuery]
            )
                ->orWhere('record_code', 'like', $likeQuery)
                ->orWhere('title', 'like', $likeQuery)
                ->orWhere('subtitle', 'like', $likeQuery)
                ->orWhere('description', 'like', $likeQuery)
                ->orWhereHas('metadata', function (Builder $metadataQuery) use ($booleanQuery, $likeQuery) {
                    $metadataQuery->whereRaw(
                        "MATCH(value_string, value_text) AGAINST (? IN BOOLEAN MODE)",
                        [$booleanQuery]
                    )
                        ->orWhere('value_string', 'like', $likeQuery)
                        ->orWhere('value_text', 'like', $likeQuery);
                })
                ->orWhereHas('creators', function (Builder $creatorQuery) use ($likeQuery) {
                    $creatorQuery->where('creators.name', 'like', $likeQuery)
                        ->orWhere('creators.normalized_name', 'like', $likeQuery);
                })
                ->orWhereHas('subjects', function (Builder $subjectQuery) use ($likeQuery) {
                    $subjectQuery->where('subjects.term', 'like', $likeQuery);
                });
        });
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['unit_type'])) {
            $query->where('unit_type', $filters['unit_type']);
        }

        if (! empty($filters['collection_type'])) {
            if (is_array($filters['collection_type'])) {
                $query->whereIn('collection_type', $filters['collection_type']);
            } else {
                $query->where('collection_type', $filters['collection_type']);
            }
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['category_slug'])) {
            $query->whereHas('category', function (Builder $categoryQuery) use ($filters) {
                $categoryQuery->where('slug', $filters['category_slug']);
            });
        }

        if (! empty($filters['year_from'])) {
            $query->where(function (Builder $yearQuery) use ($filters) {
                $yearQuery->whereNull('year_end')
                    ->orWhere('year_end', '>=', $filters['year_from']);
            });
        }

        if (! empty($filters['year_to'])) {
            $query->where(function (Builder $yearQuery) use ($filters) {
                $yearQuery->whereNull('year_start')
                    ->orWhere('year_start', '<=', $filters['year_to']);
            });
        }

        if (! empty($filters['subject'])) {
            $subject = '%' . str_replace(['%', '_'], ['\%', '\_'], $filters['subject']) . '%';

            $query->whereHas('subjects', function (Builder $subjectQuery) use ($subject) {
                $subjectQuery->where('subjects.term', 'like', $subject)
                    ->orWhere('subjects.slug', 'like', $subject);
            });
        }

        if (! empty($filters['subject_id'])) {
            $query->whereHas('subjects', function (Builder $subjectQuery) use ($filters) {
                $subjectQuery->where('subjects.id', $filters['subject_id']);
            });
        }

        if (! empty($filters['creator'])) {
            $creator = '%' . str_replace(['%', '_'], ['\%', '\_'], $filters['creator']) . '%';

            $query->whereHas('creators', function (Builder $creatorQuery) use ($creator) {
                $creatorQuery->where('creators.name', 'like', $creator)
                    ->orWhere('creators.normalized_name', 'like', $creator);
            });
        }

        if (! empty($filters['creator_id'])) {
            $query->whereHas('creators', function (Builder $creatorQuery) use ($filters) {
                $creatorQuery->where('creators.id', $filters['creator_id']);
            });
        }

        if (! empty($filters['publication_status'])) {
            $query->where('publication_status', $filters['publication_status']);
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (array_key_exists('is_featured', $filters)) {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        if (! empty($filters['language_code'])) {
            $query->where('language_code', $filters['language_code']);
        }
    }

    private function applySorting(Builder $query, array $filters): void
    {
        $sort = $filters['sort'] ?? 'latest';

        if (($filters['q'] ?? null) && $sort === 'relevance') {
            $query->orderByDesc('relevance_score')
                ->orderByDesc('created_at');

            return;
        }

        match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'year_asc' => $query->orderBy('year_start'),
            'year_desc' => $query->orderByDesc('year_start'),
            'featured' => $query->orderByDesc('is_featured')
                ->orderBy('featured_order')
                ->orderByDesc('created_at'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('created_at'),
        };
    }

    private function logSearch(array $filters, ?User $user, ?string $ipAddress, int $resultsCount): void
    {
        $query = trim((string) ($filters['q'] ?? ''));

        if ($query === '' && empty($filters)) {
            return;
        }

        SearchLog::create([
            'user_id' => $user?->id,
            'query' => $query,
            'filters' => json_encode($filters, JSON_UNESCAPED_UNICODE),
            'results_count' => $resultsCount,
            'collection_type' => $filters['unit_type'] ?? $filters['collection_type'] ?? null,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    private function toBooleanFullTextQuery(string $keyword): string
    {
        $words = preg_split('/\s+/', trim($keyword)) ?: [];

        $words = array_values(array_filter($words, function (string $word) {
            return mb_strlen($word) >= 2;
        }));

        if (empty($words)) {
            return $keyword;
        }

        return collect($words)
            ->map(function (string $word) {
                $clean = preg_replace('/[^\pL\pN\-]+/u', '', $word);

                if ($clean === '') {
                    return null;
                }

                return '+' . $clean . '*';
            })
            ->filter()
            ->implode(' ');
    }

    /**
     * @throws ValidationException
     */
    private function validateFilters(array $filters): array
    {
        return Validator::make($filters, [
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
            'is_featured' => ['sometimes', 'boolean'],
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'include_trashed' => ['sometimes', 'boolean'],
        ])->validate();
    }
}