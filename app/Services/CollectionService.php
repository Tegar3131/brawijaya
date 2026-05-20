<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Illuminate\Support\Str;

class CollectionService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CollectionVersionService $collectionVersionService
    ) {
    }

    /**
     * Membuat record dasar pada tabel collections.
     *
     * Detail library_items atau museum_items dibuat oleh service lain pada tahap berikutnya.
     *
     * @throws ValidationException
     */
    public function createBase(array $data, ?User $actor = null, ?string $reason = 'Initial collection creation'): Collection
    {
        $validated = $this->validateBaseData($data, isCreate: true);

        return DB::transaction(function () use ($validated, $actor, $reason) {
            $validated['ulid'] = $validated['ulid'] ?? (string) Str::ulid();

            if ($actor) {
                $validated['created_by'] = $validated['created_by'] ?? $actor->id;
                $validated['updated_by'] = $validated['updated_by'] ?? $actor->id;
            }

            $collection = Collection::create($validated);

            $this->collectionVersionService->record(
                collection: $collection,
                actor: $actor,
                reason: $reason,
                diff: [
                    'old' => null,
                    'new' => $collection->fresh()->toArray(),
                ]
            );

            $this->auditLogService->record(
                module: 'collection',
                action: 'create',
                event: 'collection.created',
                actor: $actor,
                auditable: $collection,
                oldValues: null,
                newValues: $collection->fresh()->toArray(),
                metadata: [
                    'reason' => $reason,
                    'record_code' => $collection->record_code,
                    'unit_type' => $collection->unit_type,
                ]
            );

            return $collection->fresh();
        });
    }

    /**
     * Mengubah data dasar collections.
     *
     * Method ini hanya mengubah kolom pada tabel collections, bukan library_items/museum_items.
     *
     * @throws ValidationException
     */
    public function updateBase(
        Collection $collection,
        array $data,
        ?User $actor = null,
        ?string $reason = 'Collection base data update'
    ): Collection {
        $allowed = $this->allowedBaseColumns();

        $payload = Arr::only($data, $allowed);

        if ($actor) {
            $payload['updated_by'] = $actor->id;
        }

        $validated = $this->validateBaseData($payload, isCreate: false);

        return DB::transaction(function () use ($collection, $validated, $actor, $reason) {
            $collection->fill($validated);

            $dirty = $collection->getDirty();

            if (empty($dirty)) {
                return $collection->fresh();
            }

            $oldValues = Arr::only($collection->getOriginal(), array_keys($dirty));

            $collection->save();

            $fresh = $collection->fresh();

            $newValues = Arr::only($fresh->toArray(), array_keys($dirty));

            $diff = [
                'old' => $oldValues,
                'new' => $newValues,
            ];

            $this->collectionVersionService->record(
                collection: $fresh,
                actor: $actor,
                reason: $reason,
                diff: $diff
            );

            $this->auditLogService->record(
                module: 'collection',
                action: 'update',
                event: 'collection.updated',
                actor: $actor,
                auditable: $fresh,
                oldValues: $oldValues,
                newValues: $newValues,
                metadata: [
                    'reason' => $reason,
                    'record_code' => $fresh->record_code,
                ]
            );

            return $fresh;
        });
    }

    public function publish(Collection $collection, ?User $actor = null, ?string $reason = 'Collection published'): Collection
    {
        return $this->updateBase(
            collection: $collection,
            data: [
                'publication_status' => 'published',
            ],
            actor: $actor,
            reason: $reason
        );
    }

    public function archive(Collection $collection, string $reason, ?User $actor = null): Collection
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('Archive reason is required.');
        }

        return DB::transaction(function () use ($collection, $reason, $actor) {
            $collection = $collection->fresh();

            $oldValues = $collection->toArray();

            $collection->fill([
                'publication_status' => 'archived',
                'archived_reason' => $reason,
                'archived_at' => now(),
                'deleted_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);

            $collection->save();
            $collection->delete();

            $snapshot = $collection->attributesToArray();

            $diff = [
                'old' => Arr::only($oldValues, [
                    'publication_status',
                    'archived_reason',
                    'archived_at',
                    'deleted_by',
                    'updated_by',
                    'deleted_at',
                ]),
                'new' => Arr::only($snapshot, [
                    'publication_status',
                    'archived_reason',
                    'archived_at',
                    'deleted_by',
                    'updated_by',
                    'deleted_at',
                ]),
            ];

            $this->collectionVersionService->recordSnapshot(
                collection: $collection,
                snapshot: $snapshot,
                actor: $actor,
                reason: $reason,
                diff: $diff
            );

            $this->auditLogService->record(
                module: 'collection',
                action: 'archive',
                event: 'collection.archived',
                actor: $actor,
                auditable: $collection,
                oldValues: $diff['old'],
                newValues: $diff['new'],
                metadata: [
                    'reason' => $reason,
                    'record_code' => $collection->record_code,
                ]
            );

            return $collection;
        });
    }

    public function restore(Collection $collection, ?User $actor = null, ?string $reason = 'Collection restored'): Collection
    {
        return DB::transaction(function () use ($collection, $actor, $reason) {
            $collection = Collection::withTrashed()->findOrFail($collection->id);

            $oldValues = $collection->attributesToArray();

            if ($collection->trashed()) {
                $collection->restore();
            }

            $collection->fill([
                'publication_status' => 'draft',
                'archived_reason' => null,
                'archived_at' => null,
                'deleted_by' => null,
                'updated_by' => $actor?->id,
            ]);

            $collection->save();

            $fresh = $collection->fresh();

            $diff = [
                'old' => Arr::only($oldValues, [
                    'publication_status',
                    'archived_reason',
                    'archived_at',
                    'deleted_by',
                    'deleted_at',
                ]),
                'new' => Arr::only($fresh->toArray(), [
                    'publication_status',
                    'archived_reason',
                    'archived_at',
                    'deleted_by',
                    'deleted_at',
                ]),
            ];

            $this->collectionVersionService->record(
                collection: $fresh,
                actor: $actor,
                reason: $reason,
                diff: $diff
            );

            $this->auditLogService->record(
                module: 'collection',
                action: 'restore',
                event: 'collection.restored',
                actor: $actor,
                auditable: $fresh,
                oldValues: $diff['old'],
                newValues: $diff['new'],
                metadata: [
                    'reason' => $reason,
                    'record_code' => $fresh->record_code,
                ]
            );

            return $fresh;
        });
    }

    /**
     * @throws ValidationException
     */
    private function validateBaseData(array $data, bool $isCreate): array
    {
        $rules = [
            'ulid' => ['sometimes', 'nullable', 'string', 'max:26'],
            'record_code' => [
                $isCreate ? 'required' : 'sometimes',
                'string',
                'max:80',
                Rule::unique('collections', 'record_code')->ignore($data['id'] ?? null),
            ],
            'unit_type' => [
                $isCreate ? 'required' : 'sometimes',
                Rule::in(['library', 'museum']),
            ],
            'collection_type' => [
                $isCreate ? 'required' : 'sometimes',
                'string',
                'max:80',
            ],
            'title' => [
                $isCreate ? 'required' : 'sometimes',
                'string',
                'max:500',
            ],
            'subtitle' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description' => ['sometimes', 'nullable', 'string'],
            'language_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'rights_status' => ['sometimes', 'nullable', 'string', 'max:120'],
            'date_display' => ['sometimes', 'nullable', 'string', 'max:120'],
            'year_start' => ['sometimes', 'nullable', 'integer'],
            'year_end' => ['sometimes', 'nullable', 'integer'],
            'category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'current_location_id' => ['sometimes', 'nullable', 'exists:locations,id'],
            'publication_status' => [
                'sometimes',
                Rule::in(['draft', 'published', 'restricted', 'archived']),
            ],
            'visibility' => [
                'sometimes',
                Rule::in(['public', 'member', 'internal', 'restricted']),
            ],
            'is_featured' => ['sometimes', 'boolean'],
            'featured_order' => ['sometimes', 'nullable', 'integer'],
            'created_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'updated_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'deleted_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'archived_reason' => ['sometimes', 'nullable', 'string'],
            'archived_at' => ['sometimes', 'nullable', 'date'],
        ];

        return Validator::make($data, $rules)->validate();
    }

    private function allowedBaseColumns(): array
    {
        return [
            'ulid',
            'record_code',
            'unit_type',
            'collection_type',
            'title',
            'subtitle',
            'description',
            'language_code',
            'rights_status',
            'date_display',
            'year_start',
            'year_end',
            'category_id',
            'current_location_id',
            'publication_status',
            'visibility',
            'is_featured',
            'featured_order',
            'created_by',
            'updated_by',
            'deleted_by',
            'archived_reason',
            'archived_at',
        ];
    }
}